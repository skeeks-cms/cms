<?php

namespace skeeks\cms\telephony\widgets;

use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\telephony\widgets\assets\TelephonyAsset;
use Yii;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;

class TelephonyWidget extends Widget
{
    public function run()
    {
        if (Yii::$app->user->isGuest) {
            return '';
        }

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

        TelephonyAsset::register($this->view);

        $jsConfig = Json::encode([
            'telephonyUser' => [
                'id'        => $telephonyUser->id,
                'provider_id' => $telephonyUser->cms_telephony_provider_id,
                'provider_user_num' => $telephonyUser->provider_user_num ?? null,
            ],
            'statuses' => CmsTelephonyCall::statuses(),
            'urls' => [
                'call'     => Url::to(['/cms/telephony/call']),
                'cancel'   => Url::to(['/cms/telephony/cancel']),
                'status'   => Url::to(['/cms/telephony/status']),
                'incoming' => Url::to(['/cms/telephony/incoming']),
            ],

        ]);

        $this->view->registerJs(<<<JS

$('body').append(`
<div id="telephony-call-panel" class="telephony-panel" style="display:none;">

    <div class="telephony-header">
        <span class="telephony-title">Звонок</span>
        <button class="telephony-close" type="button" aria-label="Закрыть">×</button>
    </div>

    <div class="telephony-body">

        <div class="telephony-entities">

            <!-- Компания -->
            <div class="telephony-party telephony-company" style="display:none;">
                <div class="telephony-avatar telephony-avatar-company">
                    <img class="telephony-company-img" />
                </div>
                <div class="telephony-party-info">
                    <div class="telephony-party-name telephony-company-name"></div>
                    <div class="telephony-party-sub">Компания</div>
                </div>
            </div>

            <!-- Клиент -->
            <div class="telephony-party telephony-client" style="display:none;">
                <div class="telephony-avatar telephony-avatar-client">
                    <img class="telephony-client-img" />
                </div>
                <div class="telephony-party-info">
                    <div class="telephony-party-name telephony-client-name"></div>
                    <div class="telephony-party-sub">Контакт</div>
                </div>
            </div>

        </div>

        <div class="telephony-phone"></div>
        <div class="telephony-status"></div>

    </div>

    <div class="telephony-actions">
        <button class="telephony-cancel btn btn-danger btn-sm">
            Отменить
        </button>
    </div>

</div>

`);

sx.Telephony = new sx.classes.Telephony({$jsConfig});
JS
        );

        return "";
    }
}
