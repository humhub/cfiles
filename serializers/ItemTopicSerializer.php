<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\serializers;

use humhub\modules\content\models\Content;
use humhub\modules\topic\models\Topic;

/**
 * The topics of a file or folder as the rows carry them (`topics`): `[{id, name, color}]`, in
 * the order of the topic list (global ones first, then by sort order and name). What the edit
 * form preselects and a row shows; the container of a topic is left out — an item's topics are
 * those of its own container, or global ones.
 *
 * @since 1.0
 */
final class ItemTopicSerializer
{
    /**
     * The topics of one content.
     */
    public static function forContent(Content $content): array
    {
        return self::forContents([(int)$content->id])[(int)$content->id] ?? [];
    }

    /**
     * The topics of several contents in one query, by content id — every id a key, `[]` for
     * a content without topics. A page of rows asks once, not once per row.
     *
     * @param int[] $contentIds
     * @return array<int, array[]>
     */
    public static function forContents(array $contentIds): array
    {
        $result = array_fill_keys(array_map('intval', $contentIds), []);
        if ($result === []) {
            return [];
        }

        $rows = Topic::find()
            ->addSelect(['relation_content_id' => 'content_tag_relation.content_id'])
            ->innerJoin('content_tag_relation', 'content_tag_relation.tag_id = content_tag.id')
            ->andWhere(['content_tag_relation.content_id' => array_keys($result)])
            // The rows as they are: an active query with a join drops rows of the same topic
            // (one per content here) as duplicates.
            ->createCommand()
            ->queryAll();

        foreach ($rows as $row) {
            $result[(int)$row['relation_content_id']][] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'color' => $row['color'] ?: null,
            ];
        }

        return $result;
    }
}
