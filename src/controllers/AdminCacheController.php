<?php
/**
 * ClearController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\admin\AdminController;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\sx\Dir;
use yii\caching\TagDependency;
use yii\db\Schema;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCacheController extends BackendController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Управление кэшем");
        parent::init();
    }

    public function actions()
    {
        return [
            "invalidate" => [
                "class"    => BackendAction::className(),
                "name"     => \Yii::t('skeeks/cms', 'Clearing temporary data'),
                "callback" => [$this, 'actionInvalidate'],
            ],
        ];
    }

    public function actionInvalidate()
    {
        $rr = new RequestResponse();
        if ($rr->isRequestAjaxPost()) {

            TagDependency::invalidate(\Yii::$app->cache, [
                \Yii::$app->skeeks->site->cacheTag
            ]);

            /**
             * @see Schema
             */
            TagDependency::invalidate(\Yii::$app->cache, [
                md5(serialize([
                    Schema::class,
                    \Yii::$app->db->dsn,
                    \Yii::$app->db->username,
                ]))
            ]);

            $rr->success = true;
            $rr->message = \Yii::t('skeeks/cms', 'Cache cleared');

            return $rr;
        }

        return $this->render('index', [
            'clearDirs' => $clearDirs,
        ]);
    }


}