<?php
/**
 * AdminSystemController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Comment;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminSystemController extends AdminController
{
    public function init()
    {
        $this->_label                   = "";
        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'session' => ['post'],
                ],
            ],
        ]);
    }

    public function actionSession()
    {

        $site = \Yii::$app->request->get('site');
        if ($site)
        {
            \Yii::$app->getSession()->set('site', $site);
        }

        $lang = \Yii::$app->request->get('lang');
        if ($lang)
        {
            \Yii::$app->getSession()->set('lang', $lang);
        }

        return $this->redirect(\Yii::$app->request->getReferrer());
    }
}
