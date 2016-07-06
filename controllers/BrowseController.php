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
use humhub\modules\post\models\Post;

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
        $fileList = $this->renderAllPostedFilesList(true);
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'currentFolder' => $this->getAllPostedFilesFolder(),
            'fileList' => $fileList['view'],
            'itemCount' => $fileList['itemCount']
        ]);
    }

    /**
     * Load all files and folders of the current folder from the database and get an array of them.
     *
     * @param array $orderBy
     *            orderBy array appended to the query
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function getItemsList($orderBy = ['title' => SORT_ASC])
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
        $filesQuery->orderBy($orderBy);
        $foldersQuery->orderBy($orderBy);
        return array_merge($foldersQuery->all(), $filesQuery->all());
    }

    /**
     * Returns file list
     *
     * @param boolean $withItemCount
     *            true -> also calculate and return the item count.
     * @param array $orderBy
     *            orderBy array appended to the query
     * @return multitype:array | string The rendered view or an array of the rendered view and the itemCount.
     */
    protected function renderFileList($withItemCount = false, $orderBy = null)
    {
        $items = $this->getItemsList($orderBy);
        
        $content_file_wrapper = [];
        
        foreach ($items as $file) {
            $content_file_wrapper[] = [
                'file' => $file,
                'content' => ''
            ];
        }
        
        $view = $this->renderAjax('@humhub/modules/cfiles/views/browse/fileList', [
            'items' => $content_file_wrapper,
            'contentContainer' => $this->contentContainer,
            'crumb' => $this->generateCrumb(),
            'errorMessages' => $this->errorMessages,
            'currentFolder' => $this->getCurrentFolder(),
            'allPostedFilesCount' => $this->getCurrentFolder()->id === self::ROOT_ID ? count($this->getAllPostedFilesList()) : 0
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
     * @param array $orderBy
     *            orderBy array appended to the query
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function getAllPostedFilesList($orderBy = ['file.updated_at' => SORT_DESC, 'file.title' => SORT_ASC])
    {
        // Get Posted Files
        $query = \humhub\modules\file\models\File::find();
        // join comments to the file if available
        $query->join('LEFT JOIN', 'comment', '(file.object_id=comment.id AND file.object_model=' . Yii::$app->db->quoteValue(Comment::className()) . ')');
        // join parent post of comment or file
        $query->join('LEFT JOIN', 'content', '(comment.object_model=content.object_model AND comment.object_id=content.object_id) OR (file.object_model=content.object_model AND file.object_id=content.object_id)');
        if (version_compare(Yii::$app->version, '1.1', 'lt')) {
            // select only the one for the given content container for Yii version < 1.1
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
            // select only the one for the given content container for Yii version >= 1.1
            $query->andWhere([
                'content.contentcontainer_id' => $this->contentContainer->contentContainerRecord->id
            ]);
        }
        // only accept Posts as the base content, so stuff from sumbmodules like files itsself or gallery will be excluded
        $query->andWhere([
            'or',
            [
                '=',
                'comment.object_model',
                Post::className()
            ],
            [
                '=',
                'file.object_model',
                Post::className()
            ]
        ]);
        // Get Files from comments
        return $query->all();
    }

    /**
     * Render all posted files from the database and get an array of them.
     *
     * @param boolean $withItemCount
     *            true -> also calculate and return the item count.
     * @param array $orderBy
     *            orderBy array appended to the query
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function renderAllPostedFilesList($withItemCount = false, $orderBy = null)
    {
        $items = $this->getAllPostedFilesList($orderBy);
        
        $content_file_wrapper = [];
        
        foreach ($items as $file) {
            $content_file_wrapper[] = [
                'file' => $file,
                'content' => $this->getBasePost($file)
            ];
        }
        
        $view = $this->renderAjax('@humhub/modules/cfiles/views/browse/fileList', [
            'items' => $content_file_wrapper,
            'contentContainer' => $this->contentContainer,
            'crumb' => $this->generateCrumb(),
            'errorMessages' => $this->errorMessages,
            'currentFolder' => $this->getCurrentFolder(),
            'allPostedFilesCount' => 0
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
}
