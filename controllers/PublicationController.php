<?php
/**
 * PublicationController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */


namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\models\Publication;
use skeeks\cms\models\searchs\Publication as PublicationSearch;
use Yii;


/**
 * Site controller
 */
class PublicationController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return
        [
            'view' =>
            [
                'class'                 => 'skeeks\cms\actions\ViewModelActionSeo',
                'modelClassName'        => Publication::className(),
            ],
        ];
    }


    public function actionIndex()
    {
        $searchModel = new PublicationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
