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

    /**
     * Force redirect to index.
     * This is needed for the wall entry nav-pills.
     */
    public function actionRedirect()
    {
        $fid = (int) Yii::$app->request->get('id', self::ROOT_ID);
        return $this->redirect($this->contentContainer->createUrl('/cfiles/browse/index', [
            'fid' => $fid
        ]));
    }

    public function actionIndex()
    {
        $orderBy = Yii::$app->request->get('order_by');
        $sortOrder = Yii::$app->request->get('sort_order');
        
        $filesOrder = NULL;
        $foldersOrder = NULL;
        
        switch ($orderBy) {
        	case "size":
        	    // default is asc for ordering by size
        	    $sortOrder = $sortOrder == 'desc' ? SORT_DESC : SORT_ASC;
        	    // value has to be casted for proper result
        	    $filesOrder = ['cast(size as unsigned)' => $sortOrder];
        	    // Note: folders are not affected of ordering by size
        	    break;
    	    case "updated_at":
    	        // default is desc for ordering by date, new files/folders on top!
    	        $sortOrder = $sortOrder == 'asc' ? SORT_ASC : SORT_DESC;
                $filesOrder = ['content.updated_at' => $sortOrder];
                $foldersOrder = ['content.updated_at' => $sortOrder];
    	        break;
	        case "title":
	            $sortOrder = $sortOrder == 'desc' ? SORT_DESC : SORT_ASC;
	            $filesOrder = ['title' => $sortOrder];
	            $foldersOrder = ['title' => $sortOrder];
    	       break;        
    	    default:
    	        // if no ordering is specified here the default ordering defined in the called methods is used
    	        break;
        }
        
        $fileList = $this->renderFileList(true, $filesOrder, $foldersOrder);
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'currentFolder' => $this->getCurrentFolder(),
            'fileList' => $fileList['view'],
            'itemCount' => $fileList['itemCount']
        ]);
    }

    /**
     * Returns file list
     *
     * @param boolean $withItemCount
     *            true -> also calculate and return the item count.
     * @param array $filesOrder
     *            orderBy array appended to the files query
     * @param array $foldersOrder
     *            orderBy array appended to the folders query
     * @return multitype:array | string The rendered view or an array of the rendered view and the itemCount.
     */
    protected function renderFileList($withItemCount = false, $filesOrder = NULL, $foldersOrder = NULL)
    {
        if($this->getCurrentFolder()->isAllPostedFiles()) {
            return $this->renderAllPostedFilesList($withItemCount, $filesOrder, $foldersOrder);
        }
        
        $items = $this->getItemsList($filesOrder, $foldersOrder);
        
        $view = $this->renderAjax('@humhub/modules/cfiles/views/browse/fileList', [
            'items' => $items,
            'contentContainer' => $this->contentContainer,
            'crumb' => $this->generateCrumb(),
            'errorMessages' => $this->errorMessages,
            'currentFolder' => $this->getCurrentFolder(),
        ]);
        if ($withItemCount) {
            return [
                'view' => $view,
                'itemCount' => count($items, COUNT_RECURSIVE)
            ];
        } else {
            return $view;
        }
    }

    /**
     * Load all posted files from the database and get an array of them.
     *
     * @param array $filesOrder
     *            orderBy array appended to the files query
     * @param array $foldersOrder
     *            currently unused
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function getAllPostedFilesList($filesOrder = NULL, $foldersOrder = NULL)
    {
        // set ordering default
        if(!$filesOrder) {
            $filesOrder = ['file.updated_at' => SORT_DESC, 'file.title' => SORT_ASC];
        }
        
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
        return ['postedFiles' => $query->orderBy($filesOrder)->all()];
    }

    /**
     * Render all posted files from the database and get an array of them.
     *
     * @param boolean $withItemCount
     *            true -> also calculate and return the item count.
     * @param array $filesOrder
     *            orderBy array appended to the files query
     * @param array $foldersOrder
     *            currently unused
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function renderAllPostedFilesList($withItemCount = false, $filesOrder = NULL, $foldersOrder = NULL)
    {
        $items = $this->getAllPostedFilesList($filesOrder, $foldersOrder);
        
        $view = $this->renderAjax('@humhub/modules/cfiles/views/browse/fileList', [
            'items' => $items,
            'contentContainer' => $this->contentContainer,
            'crumb' => $this->generateCrumb(),
            'errorMessages' => $this->errorMessages,
            'currentFolder' => $this->getCurrentFolder(),
        ]);
        
        if ($withItemCount) {
            return [
                'view' => $view,
                'itemCount' => count($items, COUNT_RECURSIVE)
            ];
        } else {
            return $view;
        }
    }
}
