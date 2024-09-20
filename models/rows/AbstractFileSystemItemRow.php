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
    public const COLUMN_SELECT = 'select';
    public const COLUMN_VISIBILITY = 'visibility';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_SIZE = 'size';
    public const COLUMN_TIMESTAMP = 'timestamp';
    public const COLUMN_DOWNLOAD_COUNT = 'download_count';
    public const COLUMN_SOCIAL = 'social';
    public const COLUMN_CREATOR = 'creator';
    public const COLUMN_ACTIONS = 'actions';

    public const ORDER_TYPE_NAME = 'name';
    public const ORDER_TYPE_UPDATED_AT = 'updated_at';
    public const ORDER_TYPE_DOWNLOAD_COUNT = 'download_count';
    public const ORDER_TYPE_SIZE = 'size';

    /**
     * The default database sort query with order definition e.g. 'title ASC' in string or array format
     */
    public const DEFAULT_ORDER = null;

    /**
     * Maps abstract sort names to actual database sort query fields (without order) for this row type.
     * Null is given, if this sorting is not supported by this row type.
     */
    public const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => null,
        self::ORDER_TYPE_UPDATED_AT => null,
        self::ORDER_TYPE_DOWNLOAD_COUNT => null,
        self::ORDER_TYPE_SIZE => null,
    ];

    public const DEFAULT_COLUMNS = [
        self::COLUMN_SELECT,
        self::COLUMN_VISIBILITY,
        self::COLUMN_TITLE,
        self::COLUMN_SIZE,
        self::COLUMN_TIMESTAMP,
        self::COLUMN_DOWNLOAD_COUNT,
        self::COLUMN_SOCIAL,
        self::COLUMN_CREATOR,
        self::COLUMN_ACTIONS,
    ];

    /**
     * @var bool
     */
    public $showSelect = true;
    private $_columns;

    /**
     * @return bool
     */
    abstract public function isSelectable();

    /**
     * @return bool
     */
    abstract public function isSocialActionsAvailable();

    /**
     * @return array
     */
    abstract public function getColumns();

    /**
     * Returns the actual database order for a given sort type. This function should return the default
     * order if the orderType is not supported by this Rowtype.
     * @param string $sort
     * @return string|array
     * @see AbstractFileSystemItemRow::ORDER_MAPPING
     */
    public static function translateOrder($sort = null, $order = 'ASC')
    {
        $result = static::DEFAULT_ORDER;

        if ($sort && array_key_exists($sort, static::ORDER_MAPPING)) {
            $result = static::ORDER_MAPPING[$sort] ? static::ORDER_MAPPING[$sort] . ' ' . $order : $result;
        }

        return $result;
    }

    /**
     * @param $column
     * @return bool
     */
    public function isRenderColumn($column)
    {
        if ($column === self::COLUMN_SELECT && !$this->showSelect) {
            return false;
        }

        if (!$this->_columns) {
            $this->_columns = $this->getColumns();
        }

        return in_array($column, $this->_columns);
    }

    /**
     * @return int
     */
    abstract public function getParentFolderId();

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return string
     */
    abstract public function getItemId();

    /**
     * @return int
     */
    abstract public function getContentId();

    /**
     * @return string
     */
    abstract public function getUrl();

    /**
     * @return string
     */
    abstract public function getLinkUrl();

    /**
     * @return string
     */
    abstract public function getDisplayUrl();

    /**
     * @return string
     */
    abstract public function getWallUrl();

    /**
     * @return string
     */
    abstract public function getEditUrl();

    /**
     * @return string
     */
    abstract public function getMoveUrl();

    /**
     * @return string
     */
    abstract public function getVersionsUrl();

    /**
     * @return string
     */
    abstract public function getIconClass();

    /**
     * @return string
     */
    abstract public function getTitle();

    /**
     * @return int
     */
    abstract public function getSize();

    /**
     * @return \humhub\modules\user\models\User
     */
    abstract public function getCreator();

    /**
     * @return \humhub\modules\user\models\User
     */
    abstract public function getEditor();

    /**
     * @return string
     */
    abstract public function getDescription();

    /**
     * @return int
     */
    abstract public function getDownloadCount();

    /**
     * @return string
     */
    abstract public function getUpdatedAt();

    /**
     * @return \humhub\modules\content\components\ContentActiveRecord
     */
    abstract public function getModel();

    /**
     * @return string
     */
    abstract public function getVisibilityIcon();

    /**
     * @return string
     */
    abstract public function getVisibilityTitle();

    /**
     * @return File
     */
    abstract public function getBaseFile();

    /**
     * @return bool
     */
    abstract public function canEdit();
}
