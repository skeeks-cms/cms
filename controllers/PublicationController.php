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

use skeeks\cms\Controller;
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
    public function behaviors()
    {
        return [];
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

    /**
     * @param $seo_page_name
     * @return string
     */
    public function actionView($seo_page_name)
    {
        $model = Publication::findOne(["seo_page_name" => $seo_page_name]);

        return $this->render('view', [
            'model'         => $model,
        ]);
    }

}
