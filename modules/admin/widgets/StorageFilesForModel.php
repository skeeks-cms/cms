<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.04.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\models\Search;
use skeeks\cms\models\StorageFile;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class StorageFilesForModel
 * @package skeeks\cms\modules\admin\widgets
 */
class StorageFilesForModel extends Widget
{
    /**
     * @var null
     */
    public $model         = null;


    public function run()
    {
        if (\Yii::$app->request->isPost)
        {
            $group = \Yii::$app->request->post('group');
            \Yii::$app->getSession()->set('cms-admin-files-group', $group);
        } else if (\Yii::$app->request->get("group"))
        {
            $group = \Yii::$app->request->get('group');
            \Yii::$app->getSession()->set('cms-admin-files-group', $group);
        } else
        {
            $group = \Yii::$app->getSession()->get('cms-admin-files-group');
        }

        $search         = new Search(StorageFile::className());
        $dataProvider   = $search->search(\Yii::$app->request->queryParams);
        $searchModel    = $search->getLoadedModel();

        $dataProvider->query->andWhere($this->model->getRef()->toArray());

        if ($group)
        {
            if ($groupObject = $this->model->getFilesGroups()->getComponent($group))
            {
                $dataProvider->query->andWhere(['src' => (array) $groupObject->items]);
            }
        }

        $controller = \Yii::$app->cms->moduleCms()->createControllerByID("admin-storage-files");

        return $this->render('storage-files-for-model',[
            "model"             => $this->model,
            'searchModel'       => $searchModel,
            'dataProvider'      => $dataProvider,
            'controller'        => $controller,
            'group'              => $group,
            'mode'              => \Yii::$app->request->get("mode"),
        ]);
    }
}