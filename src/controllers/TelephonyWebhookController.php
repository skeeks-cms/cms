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
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUser;
use Yii;
use skeeks\cms\models\CmsTelephonyProvider;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class TelephonyWebhookController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * POST /cms/telephony-webhook?id={id}
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $providerId = \Yii::$app->request->get("id");

        \Yii::info("telephony webhook", "telephony");
        /**
         * @var $provider CmsTelephonyProvider
         */
        try {
            $provider = CmsTelephonyProvider::findOne($providerId);
            if (!$provider) {
                return ['success' => false, 'error' => 'Unknown provider'];
            }

            \Yii::info("\t{$provider->id}-{$provider->component}", "telephony");

            $handler = $provider->handler;

            $payload = Yii::$app->request->getBodyParams();
            if (!$payload) {
                $payload = Yii::$app->request->get();
            }
            $headers = Yii::$app->request->headers->toArray();

            if (!$handler->verifyWebhook($headers, $payload)) {
                return ['success' => false, 'error' => 'Verification failed'];
            }

            /*\Yii::info("\tget:" . print_r(Yii::$app->request->get(), true), "telephony");
            \Yii::info("\theaders:" . print_r($headers, true), "telephony");
            \Yii::info("\tpost:" . print_r($payload, true), "telephony");*/
            $data = $handler->normalizeWebhook($payload);

            \Yii::info("\tformated" . print_r($data, true), "telephony");

            // Некоторые события Sipuni не являются звонками
            if (empty($data['provider_call_id'])) {
                \Yii::info("\tэто не звонок" . print_r($data, true), "telephony");
                return ['success' => true, 'error' => 'Это не звонок'];
            }

            $call = CmsTelephonyCall::find()
                ->where([
                    'provider_call_id' => $data['provider_call_id'],
                    'cms_telephony_provider_id' => $provider->id,
                ])
                ->one();

            if (!$call) {
                $call = new CmsTelephonyCall();
                $call->cms_telephony_provider_id = $provider->id;
                $call->provider_call_id = $data['provider_call_id'];
            }

            $call->setAttributes([
                'direction'    => $data['direction'] ?? $call->direction,
                'provider_phone_from'   => $data['provider_phone_from'] ?? $call->provider_phone_from,
                'provider_phone_to'     => $data['provider_phone_to'] ?? $call->provider_phone_to,
                'status'       => $data['status'] ?? $call->status,
                'duration'     => $data['duration'] ?? $call->duration,
                'started_at'   => $data['started_at'] ?? $call->started_at,
                'failed_reason'   => $data['failed_reason'] ?? $call->failed_reason,
                'ended_at'     => $data['ended_at'] ?? $call->ended_at,
                'record_url'   => $data['record_url'] ?? $call->record_url,
                'provider_data'  => $data['provider_data'] ?? $call->provider_data,
                'provider_user_id'  => $data['provider_user_id'] ?? $call->provider_user_id,
                'provider_user_num'  => $data['provider_user_num'] ?? $call->provider_user_num,
                'client_phone'  => $data['client_phone'] ?? $call->client_phone,
                'provider_phone'  => $data['provider_phone'] ?? $call->provider_phone,
            ], false);

            if (!$call->save()) {
                Yii::error(print_r($call->errors, true), 'telephony');
                return ['success' => false, 'error' => 'DB save error'];
            }

            /**
             * @var $cmsTelephonyUser CmsTelephonyUser|null
             */
            $cmsTelephonyUser = null;
            if ($provider->id && $call->provider_user_num) {
                $cmsTelephonyUser = CmsTelephonyUser::find()
                    ->andWhere(['cms_telephony_provider_id' => $provider->id])
                    ->andWhere(['provider_user_num' => $call->provider_user_num])
                    ->one();

                if (!$cmsTelephonyUser) {
                    $cmsTelephonyUser = new CmsTelephonyUser();
                    $cmsTelephonyUser->cms_telephony_provider_id = $provider->id;
                    $cmsTelephonyUser->provider_user_num = $call->provider_user_num;
                    if (!$cmsTelephonyUser->save()) {
                        Yii::error(print_r($cmsTelephonyUser->errors, true), 'telephony');
                        $cmsTelephonyUser = null;
                    }
                }
            }

            //Определить сотрудника
            if (!$call->cms_worker_user_id && $cmsTelephonyUser) {
                if ($cmsTelephonyUser->cms_worker_user_id) {
                    $call->cms_worker_user_id = $cmsTelephonyUser->cms_worker_user_id;
                }
            }

            //Определить клиента
            if (!$call->cms_user_id) {
                $user = CmsUser::find()->phone($call->client_phone)->one();
                if ($user) {
                    $call->cms_user_id = $user->id;

                    $cmsLog = new CmsLog();
                    $cmsLog->model_code = (new CmsUser())->skeeksModelCode;
                    $cmsLog->model_id = $user->id;
                    $cmsLog->log_type = CmsLog::LOG_TYPE_PHONE_CALL;
                    $cmsLog->data = $call->toArray([
                        'id',
                        'direction',
                        'client_phone',
                    ]);
                    $cmsLog->save();

                    $cmsLog->created_by = $call->cms_worker_user_id;
                    $cmsLog->updated_by = $call->cms_worker_user_id;
                    $cmsLog->save();
                }


            }

            //Определить клиента
            if (!$call->cms_company_id) {
                $company = CmsCompany::find()->phone($call->client_phone)->one();
                if ($company) {
                    $call->cms_company_id = $company->id;

                    $cmsLog = new CmsLog();
                    $cmsLog->model_code = (new CmsCompany())->skeeksModelCode;
                    $cmsLog->model_id = $company->id;

                    $cmsLog->log_type = CmsLog::LOG_TYPE_PHONE_CALL;
                    $cmsLog->data = $call->toArray([
                        'id',
                        'direction',
                        'client_phone',
                    ]);
                    $cmsLog->save();

                    $cmsLog->created_by = $call->cms_worker_user_id;
                    $cmsLog->updated_by = $call->cms_worker_user_id;
                    $cmsLog->save();
                }

            }

            if (!$call->save()) {
                Yii::error(print_r($call->errors, true), 'telephony');
                return ['success' => false, 'error' => 'DB save error additional'];
            }


            if ($call->status == CmsTelephonyCall::STATUS_ANSWERED && $call->record_url) {
                $call->provider->handler->saveRecord($call);
            }


            return ['success' => true];

        } catch (\Throwable $e) {
            Yii::error($e, 'telephony');
            return ['success' => false, 'error' => 'Internal error'];
        }
    }
}
