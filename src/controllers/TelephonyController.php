<?php
/**
 * StorageFilesController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */


namespace skeeks\cms\controllers;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUser;
use Yii;
use skeeks\cms\models\CmsTelephonyProvider;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class TelephonyController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return $this->asJson([
                'success' => false,
                'message' => 'Not authorized'
            ]);
        }

        return parent::beforeAction($action);
    }

    /**
     * Исходящий звонок
     */
    public function actionCall()
    {
        $phone = Yii::$app->request->post('phone');
        if (!$phone) {
            return ['success' => false, 'message' => 'Phone is empty'];
        }

        $telephonyUser = $this->_getTelephonyUser();
        if (!$telephonyUser) {
            return ['success' => false, 'message' => 'Telephony not configured'];
        }

        $result = $telephonyUser->provider->handler->call($phone, $telephonyUser);

        return $result;
    }

    public function actionCancel()
    {
        $providerCallId = Yii::$app->request->post('callId');
        $telephonyUser = $this->_getTelephonyUser();

        if (!$telephonyUser || !$providerCallId) {
            return ['success' => false];
        }

        $telephonyUser
            ->provider
            ->handler
            ->cancel($providerCallId);

        $call = CmsTelephonyCall::find()
            ->where([
                'cms_telephony_provider_id' => $telephonyUser->provider->id,
                'provider_call_id' => $providerCallId,
            ])
            ->one();

        if ($call && !$call->isFinished) {
            $call->status = CmsTelephonyCall::STATUS_FAILED;
            $call->failed_reason = CmsTelephonyCall::FAILED_CANCEL;
            $call->ended_at = time();
            $call->save(false, ['status', 'failed_reason', 'ended_at']);
        }

        return ['success' => true];
    }

    /**
     * Polling статуса (входящие + исходящие)
     */
    public function actionStatus()
    {
        if (!$callId = \Yii::$app->request->get("callId")) {
            return ['success' => true, 'hasCall' => false];
        }

        $telephonyUser = $this->_getTelephonyUser();

        if (!$telephonyUser) {
            return ['success' => true, 'hasCall' => false];
        }

        /**
         * @var $call CmsTelephonyCall
         */
        /** @var CmsTelephonyCall|null $call */
        $call = CmsTelephonyCall::find()
            ->where([
                'cms_telephony_provider_id' => $telephonyUser->provider->id,
                'provider_call_id' => $callId,
            ])
            ->one();

        if (!$call) {
            return [
                'success' => true,
                'hasCall' => false,
            ];
        }

        $companyData = [];
        if ($call->cms_company_id) {
            $companyData = [
                'id' => $call->company->id,
                'name' => $call->company->name,
                'image_src' => $call->company->cmsImage ? $call->company->cmsImage->src : null,
            ];
        }

        $clientData = [];
        if ($call->cms_user_id) {
            $clientData = [
                'id' => $call->user->id,
                'name' => $call->user->shortDisplayName,
                'image_src' => $call->user->image ? $call->user->image->src : null,
            ];
        }
        return [
            'success' => true,
            'hasCall' => true,
            'call' => [
                'id' => $call->id,

                'direction' => $call->direction,
                'status' => $call->status,
                'status_text' => $call->statusAsText,
                'is_finished' => $call->isFinished,

                'company' => $companyData, //Компания
                'client' => $clientData, //Конкретный человек - клиент

                'cms_worker_user_id' => $call->cms_worker_user_id,

                'client_phone' => $call->client_phone, //Телефон клиента на который мы звоним или с которого он звонит нам
            ]
        ];
    }

    /**
     * (опционально) отдельный endpoint под входящие
     */
    public function actionIncoming()
    {
        $telephonyUser = $this->_getTelephonyUser();
        if (!$telephonyUser) {
            return ['success' => true, 'hasCall' => false];
        }

        $activeStatuses = [
            CmsTelephonyCall::STATUS_RINGING,
            CmsTelephonyCall::STATUS_CONVERSATION,
            CmsTelephonyCall::STATUS_NEW,
        ];

        $callQuery = function () use ($telephonyUser, $activeStatuses) {
            return CmsTelephonyCall::find()
                ->where([
                    'cms_telephony_provider_id' => $telephonyUser->provider->id,
                ])
                ->andWhere(['status' => $activeStatuses])
                ->orderBy(['id' => SORT_DESC]);
        };

        $call = $callQuery()
            ->andWhere(['provider_user_num' => $telephonyUser->provider_user_num])
            ->one();

        if (!$call) {
            $call = $callQuery()
                ->andWhere(['cms_worker_user_id' => Yii::$app->user->id])
                ->one();
        }

        if (!$call) {
            $call = $callQuery()->one();
        }

        if (!$call) {
            return ['success' => true, 'hasCall' => false];
        }

        return [
            'success' => true,
            'hasCall' => true,
            'call' => [
                'provider_call_id' => $call->provider_call_id,
                'direction' => $call->direction,
                'status' => $call->status,
                'status_text' => $call->statusAsText,
                'is_finished' => $call->isFinished,
                'client_phone' => $call->client_phone,

                'company' => $call->cms_company_id ? [
                    'id' => $call->company->id,
                    'name' => $call->company->name,
                    'image_src' => $call->company->cmsImage?->src,
                ] : null,

                'client' => $call->cms_user_id ? [
                    'id' => $call->user->id,
                    'name' => $call->user->shortDisplayName,
                    'image_src' => $call->user->image?->src,
                ] : null,
            ],
        ];
    }

    /**
     * ===== helpers =====
     */

    /**
     * @return CmsTelephonyUser|null
     */
    protected function _getTelephonyUser(): ?CmsTelephonyUser
    {
        return CmsTelephonyUser::find()
            ->where([
                'cms_worker_user_id' => Yii::$app->user->id,
                'is_active' => 1,
            ])
            ->one();
    }
}
