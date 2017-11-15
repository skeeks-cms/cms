<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 16.10.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\base\Component;
use skeeks\cms\components\marketplace\models\PackageModel;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\models\CmsLang;
use skeeks\cms\models\Comment;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;

/**
 * Class AdminAjaxController
 * @package skeeks\cms\controllers
 */
class AdminAjaxController extends AdminController
{
    public function getPermissionName()
    {
        return "";
    }

    public function actionSetLang()
    {
        $rr = new RequestResponse();

        $newLang = \Yii::$app->request->post('code');
        $cmsLang = CmsLang::find()->active()->andWhere(['code' => $newLang])->one();

        if (!$cmsLang) {
            $rr->message = 'Указанный язык отлючен или удален';
            $rr->success = false;
            return $rr;
        }

        $rr->success = true;

        $component = clone \Yii::$app->admin;
        $component->setCmsUser(\Yii::$app->user)->setOverride(Component::OVERRIDE_USER);
        $component->languageCode = $cmsLang->code;

        if (!$component->save(true, ['languageCode'])) {
            $rr->message = 'Не удалось сохранить настройки: ';
            $rr->success = false;
            return $rr;
        }

        return $rr;
    }
}
