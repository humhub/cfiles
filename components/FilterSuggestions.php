<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\listing\ListContext;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\topic\models\Topic;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * What the file browser's picker filters suggest before anything is typed (the core filters'
 * `suggest`): the spaces, authors and topics of the files the user may read, the most recently
 * changed first.
 *
 * The files are those of the context's container (the file browser), else of every space of the
 * global files page ({@see GlobalFolderList::scopeQuery()}) — read through the same queries as
 * the list ({@see FolderListBuilder::fileQuery()}), so nothing is suggested the list would not show.
 *
 * @since 1.0
 */
final class FilterSuggestions
{
    /**
     * The spaces of the global files page: those with the latest files first, then the others
     * by name.
     *
     * @return int[] space ids
     */
    public static function spaces(ListContext $context, int $limit): array
    {
        $spaces = GlobalFolderList::scopeQuery($context)->orderBy(['space.name' => SORT_ASC])->all();
        if ($spaces === []) {
            return [];
        }

        $byContainer = [];
        foreach ($spaces as $space) {
            $byContainer[(int)$space->contentcontainer_id] = (int)$space->id;
        }

        $recent = self::latestBy(self::files($spaces, $context), 'content.contentcontainer_id', $limit);
        $ids = array_map(static fn($containerId) => $byContainer[(int)$containerId], $recent);

        return array_slice(array_values(array_unique([...$ids, ...array_values($byContainer)])), 0, $limit);
    }

    /**
     * The authors of the latest files.
     *
     * @return int[] user ids
     */
    public static function authors(ListContext $context, int $limit): array
    {
        $containers = self::containers($context);

        return $containers === [] ? [] : self::latestBy(self::files($containers, $context), 'content.created_by', $limit);
    }

    /**
     * The topics of the latest files.
     *
     * @return int[] topic ids
     */
    public static function topics(ListContext $context, int $limit): array
    {
        $containers = self::containers($context);
        if ($containers === []) {
            return [];
        }

        $files = self::files($containers, $context)
            ->innerJoin('content_tag_relation cfiles_suggest_relation', 'cfiles_suggest_relation.content_id = content.id')
            ->innerJoin('content_tag cfiles_suggest_tag', 'cfiles_suggest_tag.id = cfiles_suggest_relation.tag_id')
            ->andWhere(['cfiles_suggest_tag.module_id' => (new Topic())->moduleId]);

        return self::latestBy($files, 'cfiles_suggest_tag.id', $limit);
    }

    /**
     * @return ContentContainerActiveRecord[]
     */
    private static function containers(ListContext $context): array
    {
        return $context->container !== null
            ? [$context->container]
            : GlobalFolderList::scopeQuery($context)->all();
    }

    private static function files(array $containers, ListContext $context): ActiveQuery
    {
        return (new FolderListBuilder($containers, $context))->fileQuery();
    }

    /**
     * The distinct values of a column over the files, the one with the most recent file first.
     *
     * @return int[]
     */
    private static function latestBy(ActiveQuery $files, string $column, int $limit): array
    {
        return array_map('intval', $files
            ->select([$column])
            ->groupBy([$column])
            ->orderBy(new Expression('MAX(' . FolderList::LAST_CHANGE . ') DESC, ' . $column . ' ASC'))
            ->limit($limit)
            ->column());
    }
}
