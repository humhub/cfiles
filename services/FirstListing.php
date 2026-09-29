<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\components\listing\FilterableList;
use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\components\listing\ListValidationException;
use yii\web\HttpException;

/**
 * The list a file browser page embeds for its first paint, built with the filters and the sort
 * the page URL carries (the `FilterBar` mirrors them there), so the first paint matches the URL
 * and the bar has nothing to apply on mount. Used by the space's page
 * ({@see \humhub\modules\cfiles\controllers\BrowseController}) and the global files page
 * ({@see \humhub\modules\cfiles\controllers\GlobalController}).
 *
 * Only the parameters of the list's filters and `sort` are taken from the URL — a folder, an
 * item to edit and whatever else a link carries are the page's. A value the list refuses (a
 * stale link, a hand-edited URL) is dropped rather than failing the page.
 *
 * @since 1.0
 */
final class FirstListing
{
    /**
     * @param array $query the page URL's parameters
     * @param array $fixed parameters the page sets itself, e.g. the folder (`parent`)
     * @param string[] $pageParams parameters of the list's filters that are the page's rather
     *        than the URL's — `parent`, which is `fid` there
     * @return array{builder: ListBuilder, initialFilters: array<string, string>} the list, and
     *         the values of its filters it was built with, by parameter (`''` = not set, several
     *         values comma-separated)
     * @throws HttpException 404 when the list refuses even the page's own parameters
     */
    public static function build(FilterableList $list, ListContext $context, array $query, array $fixed = [], array $pageParams = []): array
    {
        $filterParams = array_values(array_diff(self::filterParams($list), $pageParams));
        $params = array_intersect_key($query, array_flip([...$filterParams, FilterableList::SORT_PARAM]));
        // Several values are a list, whatever keys the URL gave them (`topicId[3]=…`): the list
        // would read other keys as bracket parameters of their own.
        $params = array_map(static fn($value) => is_array($value) ? array_values($value) : $value, $params);

        try {
            $builder = $list->build($fixed + $params, $context);
        } catch (ListValidationException $e) {
            // Without what was refused; failing that (an error under a key of its own, such
            // as a bracket parameter), without any of the URL's.
            $params = array_diff_key($params, $e->errors);
            try {
                $builder = $list->build($fixed + $params, $context);
            } catch (ListValidationException) {
                $params = [];
                try {
                    $builder = $list->build($fixed, $context);
                } catch (ListValidationException) {
                    // The page's own parameters (a folder it resolved) are ones the list
                    // accepts; should they ever disagree, the page does not exist rather than
                    // failing.
                    throw new HttpException(404);
                }
            }
        }

        $initialFilters = [];
        foreach ($filterParams as $param) {
            $initialFilters[$param] = self::barValue($params[$param] ?? '');
        }

        return ['builder' => $builder, 'initialFilters' => $initialFilters];
    }

    /**
     * A value of the URL as the `FilterBar` writes it: a string trimmed, an array (a filter of
     * several values sent as `topicId[]=1&topicId[]=2`) comma-separated, as the bar writes it
     * itself — anything else (a nested array) is none.
     */
    private static function barValue(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $part) {
                if (!is_string($part) && !is_int($part)) {
                    return '';
                }
                if (trim((string)$part) !== '') {
                    $parts[] = trim((string)$part);
                }
            }

            return implode(',', $parts);
        }

        return is_string($value) ? trim($value) : '';
    }

    /**
     * The parameters of the list's filters, in the order of the bar.
     *
     * @return string[]
     */
    private static function filterParams(FilterableList $list): array
    {
        $params = [];
        foreach ($list->getFilters() as $filter) {
            $params = array_merge($params, $filter->params());
        }

        return $params;
    }
}
