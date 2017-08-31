<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;

use humhub\modules\file\models\File;
use yii\base\Model;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 30.08.2017
 * Time: 23:26
 */

abstract class AbstractFileSystemItemRow extends Model
{
    const COLUMN_SELECT = 'select';
    const COLUMN_VISIBILITY = 'visibility';
    const COLUMN_TITLE = 'title';
    const COLUMN_SIZE = 'size';
    const COLUMN_TIMESTAMP = 'timestamp';
    const COLUMN_SOCIAL = 'social';
    const COLUMN_CREATOR = 'creator';

    const DEFAULT_COLUMNS = [
        self::COLUMN_SELECT,
        self::COLUMN_VISIBILITY,
        self::COLUMN_TITLE,
        self::COLUMN_SIZE,
        self::COLUMN_TIMESTAMP,
        self::COLUMN_SOCIAL,
        self::COLUMN_CREATOR
    ];

    /**
     * @var bool
     */
    public $showSelect = true;
    private $_columns;

    /**
     * @return boolean
     */
    public abstract function isSelectable();

    /**
     * @return boolean
     */
    public abstract function isSocialActionsAvailable();

    /**
     * @return array
     */
    public abstract function getColumns();

    /**
     * @param $column
     * @return bool
     */
    public function isRenderColumn($column)
    {
        if($column === self::COLUMN_SELECT && !$this->showSelect) {
            return false;
        }

        if(!$this->_columns) {
            $this->_columns = $this->getColumns();
        }

        return in_array($column, $this->_columns);
    }

    /**
     * @return integer
     */
    public abstract function getParentFolderId();

    /**
     * @return string
     */
    public abstract function getType();

    /**
     * @return string
     */
    public abstract function getItemId();

    /**
     * @return string
     */
    public abstract function getUrl();

    /**
     * @return string
     */
    public abstract function getLinkUrl();

    /**
     * @return string
     */
    public abstract function getDisplayUrl();

    /**
     * @return string
     */
    public abstract function getWallUrl();

    /**
     * @return string
     */
    public abstract function getEditUrl();

    /**
     * @return string
     */
    public abstract function getMoveUrl();

    /**
     * @return string
     */
    public abstract function getIconClass();

    /**
     * @return string
     */
    public abstract function getTitle();

    /**
     * @return string
     */
    public abstract function getSize();

    /**
     * @return \humhub\modules\user\models\User
     */
    public abstract function getCreator();

    /**
     * @return \humhub\modules\user\models\User
     */
    public abstract function getEditor();

    /**
     * @return string
     */
    public abstract function getDescription();

    /**
     * @return string
     */
    public abstract function getUpdatedAt();

    /**
     * @return \humhub\modules\content\components\ContentActiveRecord
     */
    public abstract function getModel();

    /**
     * @return string
     */
    public abstract function getVisibilityIcon();

    /**
     * @return string
     */
    public abstract function getVisibilityTitle();

    /**
     * @return File
     */
    public abstract function getBaseFile();
}