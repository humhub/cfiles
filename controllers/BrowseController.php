<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use yii\web\UploadedFile;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\comment\models\Comment;
use yii\helpers\FileHelper;
use humhub\models\Setting;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class BrowseController extends BaseController
{

    public function actionIndex()
    {
        $fileList = $this->renderFileList(true);
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'currentFolder' => $this->getCurrentFolder(),
            'fileList' => $fileList['view'],
            'itemCount' => $fileList['itemCount']
        ]);
    }

    /**
     * Action to list all posted files from the content container.
     *
     * @return string
     */
    public function actionAllPostedFiles()
    {
        $items = $this->getAllPostedFiles();
        
        $content_file_wrapper = [];
        
        foreach ($items as $file) {
            $content_file_wrapper[] = [
                'file' => $file,
                'content' => $this->getBasePost($file)
            ];
        }
        
        return $this->render('allPostedFiles', [
            'contentContainer' => $this->contentContainer,
            'items' => $content_file_wrapper
        ]);
    }

    /**
     * Returns file list
     *
     * @return type
     */
    protected function renderFileList($withItemCount = false)
    {
        $filesQuery = File::find()->joinWith('baseFile')
            ->contentContainer($this->contentContainer)
            ->readable();
        $foldersQuery = Folder::find()->contentContainer($this->contentContainer)->readable();
        $filesQuery->andWhere([
            'cfiles_file.parent_folder_id' => $this->getCurrentFolder()->id
        ]);
        $foldersQuery->andWhere([
            'cfiles_folder.parent_folder_id' => $this->getCurrentFolder()->id
        ]);
        $items = array_merge($foldersQuery->all(), $filesQuery->all());
        $view = $this->renderAjax('@humhub/modules/cfiles/views/browse/fileList', [
            'items' => $items,
            'contentContainer' => $this->contentContainer,
            'crumb' => $this->generateCrumb(),
            'errorMessages' => $this->errorMessages,
            'currentFolder' => $this->getCurrentFolder(),
            'allPostedFilesCount' => $this->getCurrentFolder()->id === self::ROOT_ID ? count($this->getAllPostedFiles()) : 0
        ]);
        if ($withItemCount) {
            return [
                'view' => $view,
                'itemCount' => count($items)
            ];
        } else {
            return $view;
        }
    }

    /**
     * Load all posted files from the database and get an array of them.
     *
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function getAllPostedFiles()
    {
        // Get Posted Files
        $query = \humhub\modules\file\models\File::find();
        $query->join('LEFT JOIN', 'comment', '(file.object_id=comment.id)');
        $query->join('LEFT JOIN', 'content', '(comment.object_model=content.object_model AND comment.object_id=content.object_id) OR (file.object_model=content.object_model AND file.object_id=content.object_id)');
        if (version_compare(Yii::$app->version, '1.1', 'lt')) {
            if ($this->contentContainer instanceof \humhub\modules\user\models\User) {
                $query->andWhere([
                    'content.user_id' => $this->contentContainer->id
                ]);
                $query->andWhere([
                    'IS',
                    'content.space_id',
                    new \yii\db\Expression('NULL')
                ]);
            } else {
                $query->andWhere([
                    'content.space_id' => $this->contentContainer->id
                ]);
            }
        } else {
            $query->andWhere([
                'content.contentcontainer_id' => $this->contentContainer->contentContainerRecord->id
            ]);
        }
        $query->andWhere([
            '<>',
            'file.object_model',
            File::className()
        ]);
        $query->orderBy([
            'file.updated_at' => SORT_DESC
        ]);
        // Get Files from comments
        return $query->all();
    }
}
