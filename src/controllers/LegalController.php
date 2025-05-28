<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsTree;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * @property CmsTree $model
 *
 * Class TreeController
 * @package skeeks\cms\controllers
 */
class LegalController extends Controller
{
    public function beforeAction($action)
    {

        \Yii::$app->breadcrumbs->append([
            'url'  => Url::home(),
            'name' => "Главная",
        ]);

        \Yii::$app->breadcrumbs->append([
            'url'  => Url::to(['/cms/legal']),
            'name' => "Правовая информация",
        ]);

        return parent::beforeAction($action);
    }
    /**
     * @return $this|string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        \Yii::$app->view->title = "Правовая информация";
        return $this->render($this->action->id);
    }

    /**
     * @return $this|string
     * @throws NotFoundHttpException
     */
    public function actionCookie()
    {
        \Yii::$app->breadcrumbs->append([
            'url'  => Url::to(['/cms/legal/cookie']),
            'name' => "Политика обработки файлов cookie",
        ]);

        \Yii::$app->view->title = "Политика обработки файлов cookie";
        return $this->render($this->action->id);
    }

    /**
     * @return $this|string
     * @throws NotFoundHttpException
     */
    public function actionPersonalData()
    {
        \Yii::$app->breadcrumbs->append([
            'url'  => Url::to(['/cms/legal/personal-data']),
            'name' => "Политика в отношении обработки персональных данных",
        ]);

        \Yii::$app->view->title = "Политика в отношении обработки персональных данных";
        return $this->render($this->action->id);
    }

    /**
     * @return $this|string
     * @throws NotFoundHttpException
     */
    public function actionPrivacyPolicy()
    {
        \Yii::$app->breadcrumbs->append([
            'url'  => Url::to(['/cms/legal/privacy-policy']),
            'name' => "Политика конфиденциальности",
        ]);

        \Yii::$app->view->title = "Политика конфиденциальности";
        return $this->render($this->action->id);
    }


}
