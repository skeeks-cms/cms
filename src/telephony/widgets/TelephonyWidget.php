<?php

namespace skeeks\cms\telephony\widgets;

use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\telephony\widgets\assets\TelephonyAsset;
use skeeks\cms\telephony\widgets\assets\TelephonySoftphoneAsset;
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

        $this->view->registerCss(<<<CSS
.telephony-panel {
    position: fixed;
    right: 20px;
    bottom: 20px;
    width: 280px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    font-family: Arial, sans-serif;
    z-index: 9999;
}

/* ===== Header ===== */

.telephony-header {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.telephony-title {
    font-weight: bold;
    font-size: 14px;
}

.telephony-close {
    border: none;
    background: none;
    font-size: 18px;
    cursor: pointer;
}

/* ===== Body ===== */

.telephony-body {
    padding: 12px;
}

.telephony-entities {
    margin-bottom: 8px;
}

/* ===== Party (company / client) ===== */

.telephony-party {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.telephony-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    overflow: hidden;
    background: #f0f0f0;
    margin-right: 10px;
    flex-shrink: 0;
}

.telephony-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Различие визуально */
.telephony-avatar-company {
    border: 2px solid #4caf50;
}

.telephony-avatar-client {
    border: 2px solid #2196f3;
}

.telephony-party-info {
    flex: 1;
}

.telephony-party-name {
    font-weight: bold;
    font-size: 14px;
    line-height: 1.2;
}

.telephony-party-sub {
    font-size: 12px;
    color: #888;
}

/* ===== Phone + Status ===== */

.telephony-phone {
    font-size: 17px;
    font-weight: bold;
    margin-top: 6px;
}

.telephony-status {
    margin-top: 4px;
    font-size: 13px;
    color: #666;
}

/* ===== Actions ===== */

.telephony-actions {
    padding: 10px 12px;
    border-top: 1px solid #eee;
    text-align: right;
}


CSS
        );

        $this->view->registerJs(<<<JS

$('body').append(`
<div id="telephony-call-panel" class="telephony-panel" style="display:none;">

    <div class="telephony-header">
        <span class="telephony-title">Звонок</span>
        <button class="telephony-close">×</button>
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
