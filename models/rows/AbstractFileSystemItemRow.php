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
    const COLUMN_DOWNLOAD_COUNT = 'download_count';
    const COLUMN_SOCIAL = 'social';
    const COLUMN_CREATOR = 'creator';
    const COLUMN_ACTIONS = 'actions';

    const ORDER_TYPE_NAME = 'name';
    const ORDER_TYPE_UPDATED_AT = 'updated_at';
    const ORDER_TYPE_DOWNLOAD_COUNT = 'download_count';
    const ORDER_TYPE_SIZE = 'size';

    /**
     * The default database sort query with order definition e.g. 'title ASC' in string or array format
     */
    const DEFAULT_ORDER = null;

    /**
     * Maps abstract sort names to actual database sort query fields (without order) for this row type.
     * Null is given, if this sorting is not supported by this row type.
     */
    const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => null,
        self::ORDER_TYPE_UPDATED_AT => null,
        self::ORDER_TYPE_DOWNLOAD_COUNT => null,
        self::ORDER_TYPE_SIZE => null,
    ];

    const DEFAULT_COLUMNS = [
        self::COLUMN_SELECT,
        self::COLUMN_VISIBILITY,
        self::COLUMN_TITLE,
        self::COLUMN_SIZE,
        self::COLUMN_TIMESTAMP,
        self::COLUMN_DOWNLOAD_COUNT,
        self::COLUMN_SOCIAL,
        self::COLUMN_CREATOR,
        self::COLUMN_ACTIONS
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
     * Returns the actual database order for a given sort type. This function should return the default
     * order if the orderType is not supported by this Rowtype.
     * @param string $sort
     * @return string|array
     * @see AbstractFileSystemItemRow::ORDER_MAPPING
     */
    public static function translateOrder($sort = null, $order = 'ASC') {
        $result = static::DEFAULT_ORDER;

        if($sort && array_key_exists($sort, static::ORDER_MAPPING)) {
            $result = static::ORDER_MAPPING[$sort] ? static::ORDER_MAPPING[$sort].' '.$order : $result;
        }

        return $result;
    }

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
     * @return int
     */
    public abstract function getContentId();

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
    public abstract function getVersionsUrl();

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
     * @return integer
     */
    public abstract function getDownloadCount();

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

    /**
     * @return boolean
     */
    public abstract function canEdit();
}