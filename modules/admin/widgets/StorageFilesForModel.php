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
    public $model               = null;
    public $gridColumns         = [];


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


        $gridColumns = [

            ['class' => 'yii\grid\SerialColumn'],

            [
                'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                'controller'    => $controller,
                'isOpenNewWindow'    => true
            ],

            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\StorageFile $model)
                {
                    if ($model->isImage())
                    {
                        \Yii::$app->view->registerCss(<<<CSS
    .sx-img-small {
        max-height: 50px;
    }
CSS
);

                        $smallImage = \Yii::$app->imaging->getImagingUrl($model->src, new \skeeks\cms\components\imaging\filters\Thumbnail());

                        return "<a href='{$model->src}' class='sx-fancybox'>" . \yii\helpers\Html::img($smallImage, [
                            'width' => '50',
                            'class' => 'sx-img-small'
                        ]) . '</a>';
                    }

                    return \yii\helpers\Html::tag('span', $model->extension, ['class' => 'label label-primary', 'style' => 'font-size: 18px;']);
                },
                'format' => 'html'
            ],



            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\StorageFile $file)
                {
                    if ($groups = $file->getFilesGroups())
                    {
                        $result = \yii\helpers\ArrayHelper::map($groups, "id", "name");

                        if ($result)
                        {
                            foreach ($result as $key => $name)
                            {
                                $result[$key] = '<span class="label label-info"><i class="glyphicon glyphicon-tag"></i> ' . $name . '</span>';
                            }
                        }

                        return implode(' ', $result);
                    }
                },
                'format' => 'html',
                'label' => 'Метки'
            ],

            'name',

            [
                'attribute' => 'mime_type',
                'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'mime_type', 'mime_type'),
            ],

            [
                'attribute' => 'extension',
                'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'extension', 'extension'),
            ],


            [
                'class' => \skeeks\cms\grid\FileSizeColumnData::className(),
                'attribute' => 'size'
            ],
        ];


        if ($this->gridColumns)
        {
            $gridColumns = $this->gridColumns;
        }


        return $this->render('storage-files-for-model',[
            "model"             => $this->model,
            'searchModel'       => $searchModel,
            'dataProvider'      => $dataProvider,
            'controller'        => $controller,
            'group'              => $group,
            'mode'              => \Yii::$app->request->get("mode"),
            'gridColumns'       => $gridColumns,
        ]);
    }
}