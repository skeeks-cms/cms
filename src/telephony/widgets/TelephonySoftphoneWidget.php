<?php

namespace skeeks\cms\telephony\widgets;

use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\telephony\widgets\assets\TelephonySoftphoneAsset;
use Yii;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;

class TelephonySoftphoneWidget extends Widget
{
    public function run()
    {
        if (Yii::$app->user->isGuest) {
            return '';
        }

        die;
        // Проверяем, есть ли у пользователя тел. учетка
        /**
         * @var $telephonyUser CmsTelephonyUser
         */
        $telephonyUser = CmsTelephonyUser::find()
            ->where([
                'cms_worker_user_id' => Yii::$app->user->id,
                'is_active' => 1,
            ])
            ->one();

        if (!$telephonyUser) {
            return '';
        }

        $configUrl = Url::to(['/telephony/sip-config']);


        TelephonySoftphoneAsset::register($this->view);

        $jsConfig = Json::encode([
            'ws_url' => $telephonyUser->ws_url,
            'sip_config' => [
                'uri' => $telephonyUser->sip_uri,
                'password' => $telephonyUser->sip_password,
                'display_name' => \Yii::$app->user->identity->shortDisplayName,
                'session_timers' => false,
            ]
        ]);

        $this->view->registerJs(<<<JS
sx.Softphone = new sx.classes.Softphone({$jsConfig});
JS
        );

        return "";
    }
}
