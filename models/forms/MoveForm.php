<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
namespace humhub\modules\cfiles\models\forms;


use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 19.08.2017
 * Time: 19:06
 */
class MoveForm extends SelectionForm
{
    /**
     * @var Folder root folder of this contentcontainer
     */
    public $root;

    /**
     * @var Folder the source folder of the selection
     */
    public $sourceFolder;

    /**
     * @var Folder the id of destination folder id
     */
    public $destId;

    /**
     * @var Folder the destination of the move event
     */
    public $destination;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->contentContainer = $this->root->content->container;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['destId', 'required'],
            ['destId', 'integer'],
            ['destId', 'validateDestination']
        ];
    }

    /**
     * @param $model MoveForm
     * @param $attribute
     */
    public function validateDestination($attribute)
    {
        $this->destination = Folder::findOne($this->destId);

        if (!$this->destination) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Destination folder not found!'));
            return;
        }

        if ($this->sourceFolder->id == $this->destination->id) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Moving to the same folder is not valid.'));
            return;
        }

        if ($this->destination->isAllPostedFiles() || $this->destination->content->container->id !== $this->contentContainer->id) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Moving to this folder is invalid.'));
            return;
        }
    }

    /**
     * @return string move action url
     */
    public function getMoveUrl()
    {
        return $this->sourceFolder->createUrl('/cfiles/move');
    }

    /**
     * @return string URL to move Content to another Container
     */
    public function getMoveToContainerUrl(): ?string
    {
        if (count($this->selection) !== 1) {
            // Only single selected File/Folder can be moved to another Container
            return null;
        }

        $item = FileSystemItem::getItemById($this->selection[0]);
        if (!$item || !$item->content->container || !$item->content->checkMovePermission()) {
            return null;
        }

        return $item->content->container->createUrl('/content/move/move', ['id' => $item->content->id]);
    }

    /**
     * Executes the actual move of the selection files from source into target.
     * @return bool
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $result = true;

        foreach ($this->selection as $selectedItemId) {
            $item = FileSystemItem::getItemById($selectedItemId);

            if (!$this->destination->moveItem($item)) {
                $this->addItemErrors($item);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param FileSystemItem $item
     */
    public function addItemErrors(FileSystemItem $item)
    {
        foreach ($item->errors as $key => $error) {
            $this->addErrors([$key => $error]);
        }
    }


}