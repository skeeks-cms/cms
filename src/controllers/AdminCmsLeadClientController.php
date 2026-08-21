<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\CmsUserPhone;
use yii\base\Event;
use yii\base\Application;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class AdminCmsLeadClientController extends AdminUserController
{
    private $_lead;
    private ?Transaction $_conversionTransaction = null;

    public function init()
    {
        parent::init();
        $this->name = 'Клиент из лида';
    }

    public function actions()
    {
        $create = ArrayHelper::getValue(parent::actions(), 'create', []);
        return [
            'create' => ArrayHelper::merge($create, [
                'class' => BackendModelCreateAction::class,
                'name' => 'Создать клиента',
                'accessCallback' => fn() => $this->lead->status === CmsLead::STATUS_IN_WORK
                    && $this->lead->canBeWorkedBy((int)\Yii::$app->user->id)
                    && !$this->lead->cms_user_id,
                'afterSaveUrl' => ['/cms/admin-cms-lead/view', 'pk' => (int)\Yii::$app->request->get('lead_id')],
                'on '.BackendModelCreateAction::EVENT_BEFORE_SAVE => function () {
                    $this->beginConversion();
                },
                'on '.BackendModelCreateAction::EVENT_AFTER_SAVE => function (Event $event) {
                    $this->linkClient($event->sender->model);
                    $this->_conversionTransaction->commit();
                },
            ]),
        ];
    }

    private function beginConversion(): void
    {
        $this->_conversionTransaction = CmsLead::getDb()->beginTransaction();
        \Yii::$app->on(Application::EVENT_AFTER_REQUEST, function (): void {
            if ($this->_conversionTransaction && $this->_conversionTransaction->isActive) {
                $this->_conversionTransaction->rollBack();
            }
        });
    }

    public function getLead(): CmsLead
    {
        if ($this->_lead === null) {
            $this->_lead = CmsLead::find()
                ->forManager()
                ->cmsSite()
                ->andWhere([CmsLead::tableName().'.id' => (int)\Yii::$app->request->get('lead_id')])
                ->one();
            if (!$this->_lead) {
                throw new NotFoundHttpException('Лид не найден.');
            }
        }
        return $this->_lead;
    }

    private function linkClient(CmsUser $client): void
    {
        $lead = CmsLead::find()->forManager()->cmsSite()->andWhere([CmsLead::tableName().'.id' => $this->lead->id])->one();
        if (!$lead || $lead->status !== CmsLead::STATUS_IN_WORK || !$lead->canBeWorkedBy((int)\Yii::$app->user->id)) {
            throw new ForbiddenHttpException('Лид недоступен для работы.');
        }
        if ($lead->cms_user_id) {
            throw new \RuntimeException('С лидом уже связан клиент.');
        }
        $lead->cms_user_id = $client->id;
        if (!$lead->save()) {
            throw new \RuntimeException(implode('; ', $lead->getFirstErrors()));
        }

        foreach ($lead->phones as $leadPhone) {
            if (CmsUserPhone::find()->andWhere(['cms_user_id' => $client->id, 'value' => $leadPhone->value])->exists()) {
                continue;
            }
            $phone = new CmsUserPhone([
                'cms_user_id' => (int)$client->id,
                'value' => $leadPhone->value,
                'name' => $leadPhone->name,
            ]);
            if (!$phone->save()) { throw new \RuntimeException(implode('; ', $phone->getFirstErrors())); }
        }
        foreach ($lead->emails as $leadEmail) {
            if (CmsUserEmail::find()->andWhere(['cms_user_id' => $client->id, 'value' => $leadEmail->value])->exists()) {
                continue;
            }
            $email = new CmsUserEmail([
                'cms_user_id' => (int)$client->id,
                'value' => $leadEmail->value,
                'name' => $leadEmail->name,
            ]);
            if (!$email->save()) { throw new \RuntimeException(implode('; ', $email->getFirstErrors())); }
        }

        $log = new CmsLog();
        $log->log_type = CmsLog::LOG_TYPE_COMMENT;
        $log->model_code = $client->skeeksModelCode;
        $log->model_id = $client->id;
        $log->model_as_text = $client->asText;
        $log->cms_user_id = $client->id;
        $log->comment = 'Клиент создан из лида №'.(int)$lead->id.' «'.Html::encode($lead->name).'».';
        if (!$log->save()) {
            throw new \RuntimeException(implode('; ', $log->getFirstErrors()));
        }
    }
}
