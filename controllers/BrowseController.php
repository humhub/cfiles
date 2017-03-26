<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use humhub\modules\comment\models\Comment;
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
        return $this->redirect($this->contentContainer->createUrl('/cfiles/browse/index', ['fid' => $fid]));
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

        return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'folder' => $this->getCurrentFolder(),
                    'canWrite' => $this->canWrite()
        ]);
    }

    public function actionFileList()
    {
        return $this->asJson([
                    'output' => $this->renderFileList()
        ]);
    }

    /**
     * Returns rendered file list.
     *
     * @param boolean $withItemCount true -> also calculate and return the item count.
     * @param array $filesOrder orderBy array appended to the files query
     * @param array $foldersOrder orderBy array appended to the folders query
     * @return array|string the rendered view or an array of the rendered view and the itemCount.
     */
    protected function renderFileList($filesOrder = null, $foldersOrder = null)
    {
        return \humhub\modules\cfiles\widgets\FileList::widget([
                    'folder' => $this->getCurrentFolder(),
                    'contentContainer' => $this->contentContainer,
                    'canWrite' => $this->canWrite(),
                    'filesOrder' => $filesOrder,
                    'foldersOrder' => $foldersOrder
        ]);
    }

}
