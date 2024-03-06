<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\models;

use humhub\modules\user\models\User;

/**
 * ItemInterface for File and Folder items
 *
 * @author luke
 */
interface ItemInterface
{
    /**
     * @return string
     */
    public function getItemId();

    /**
     * @return int
     */
    public function getContentId();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return int
     */
    public function getSize();

    /**
     * @return User|null
     */
    public function getCreator();

    /**
     * @return User|null
     */
    public function getEditor();

    /**
     * @param bool $scheme
     * @return string
     */
    public function getUrl(bool $scheme = false);

    /**
     * @return string
     */
    public function getEditUrl();
}
