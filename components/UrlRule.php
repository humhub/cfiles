<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\ContentContainerUrlRuleInterface;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Component;
use yii\web\UrlManager;
use yii\web\UrlRuleInterface;

class UrlRule extends Component implements UrlRuleInterface, ContentContainerUrlRuleInterface
{
    const DOWNLOAD_ROUTE = 'cfiles/download';

    /**
     * @inheritdoc
     */
    public function parseContentContainerRequest(ContentContainerActiveRecord $container, UrlManager $manager, string $containerUrlPath, array $urlParams)
    {
        if (substr($containerUrlPath, 0, 16) === self::DOWNLOAD_ROUTE . '/') {
            $parts = explode('/', $containerUrlPath, 4);

            if (isset($parts[2])) {
                $urlParams['guid'] = $parts[2];

                return [self::DOWNLOAD_ROUTE, $urlParams];
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function createContentContainerUrl(UrlManager $manager, string $containerUrlPath, string $route, array $params)
    {
        if ($route === self::DOWNLOAD_ROUTE && isset($params['guid'])) {
            $url = $containerUrlPath . '/' . self::DOWNLOAD_ROUTE . '/' . urlencode($params['guid']);
            unset($params['guid']);

            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $url .= '?' . $query;
            }

            return $url;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function createUrl($manager, $route, $params)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {
        return false;
    }
}
