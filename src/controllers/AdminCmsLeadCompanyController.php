<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2contractor;
use skeeks\cms\models\CmsCompanyAddress;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use yii\base\Event;
use yii\base\Application;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class AdminCmsLeadCompanyController extends AdminCmsCompanyController
{
    private $_lead;
    private ?Transaction $_conversionTransaction = null;

    public function init()
    {
        parent::init();
        $this->name = 'Компания из лида';
    }

    public function actions()
    {
        $create = ArrayHelper::getValue(parent::actions(), 'create', []);
        return [
            'create' => ArrayHelper::merge($create, [
                'class' => BackendModelCreateAction::class,
                'name' => 'Создать компанию',
                'accessCallback' => fn() => $this->lead->status === CmsLead::STATUS_IN_WORK
                    && $this->lead->canBeWorkedBy((int)\Yii::$app->user->id)
                    && !$this->lead->cms_company_id,
                'afterSaveUrl' => ['/cms/admin-cms-lead/view', 'pk' => (int)\Yii::$app->request->get('lead_id')],
                'on '.BackendModelCreateAction::EVENT_BEFORE_SAVE => function () {
                    $this->beginConversion();
                },
                'on '.BackendModelCreateAction::EVENT_AFTER_SAVE => function (Event $event) {
                    $this->linkCompany($event->sender->model);
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
            if (!$this->_lead) { throw new NotFoundHttpException('Лид не найден.'); }
        }
        return $this->_lead;
    }

    private function linkCompany(CmsCompany $company): void
    {
        $lead = CmsLead::find()->forManager()->cmsSite()->andWhere([CmsLead::tableName().'.id' => $this->lead->id])->one();
        if (!$lead || $lead->status !== CmsLead::STATUS_IN_WORK || !$lead->canBeWorkedBy((int)\Yii::$app->user->id)) {
            throw new ForbiddenHttpException('Лид недоступен для работы.');
        }
        if ($lead->cms_company_id) { throw new \RuntimeException('С лидом уже связана компания.'); }
        $lead->cms_company_id = $company->id;
        if (!$lead->save()) { throw new \RuntimeException(implode('; ', $lead->getFirstErrors())); }

        foreach ($lead->phones as $leadPhone) {
            if (CmsCompanyPhone::find()->andWhere(['cms_company_id' => $company->id, 'value' => $leadPhone->value])->exists()) {
                continue;
            }
            $phone = new CmsCompanyPhone([
                'cms_company_id' => (int)$company->id,
                'value' => $leadPhone->value,
                'name' => $leadPhone->name,
                'sort' => $leadPhone->sort,
            ]);
            if (!$phone->save()) { throw new \RuntimeException(implode('; ', $phone->getFirstErrors())); }
        }
        foreach ($lead->emails as $leadEmail) {
            if (CmsCompanyEmail::find()->andWhere(['cms_company_id' => $company->id, 'value' => $leadEmail->value])->exists()) {
                continue;
            }
            $email = new CmsCompanyEmail([
                'cms_company_id' => (int)$company->id,
                'value' => $leadEmail->value,
                'name' => $leadEmail->name,
                'sort' => $leadEmail->sort,
            ]);
            if (!$email->save()) { throw new \RuntimeException(implode('; ', $email->getFirstErrors())); }
        }
        foreach ($lead->addresses as $leadAddress) {
            if (CmsCompanyAddress::find()->andWhere(['cms_company_id' => $company->id, 'value' => $leadAddress->value])->exists()) {
                continue;
            }
            $address = new CmsCompanyAddress(['cms_company_id' => (int)$company->id]);
            foreach (['name', 'value', 'latitude', 'longitude', 'entrance', 'floor', 'apartment_number', 'comment', 'postcode', 'sort'] as $attribute) {
                $address->{$attribute} = $leadAddress->{$attribute};
            }
            if (!$address->save()) { throw new \RuntimeException(implode('; ', $address->getFirstErrors())); }
        }
        foreach ($lead->links as $leadLink) {
            if (CmsCompanyLink::find()->andWhere(['cms_company_id' => $company->id, 'url' => $leadLink->url])->exists()) {
                continue;
            }
            $link = new CmsCompanyLink([
                'cms_company_id' => (int)$company->id,
                'url' => $leadLink->url,
                'link_type' => $leadLink->link_type,
                'name' => $leadLink->name,
                'sort' => $leadLink->sort,
            ]);
            if (!$link->save()) { throw new \RuntimeException(implode('; ', $link->getFirstErrors())); }
        }
        // Requisites are shared contractor records: the company gets a link, the lead keeps its own.
        foreach ($lead->lead2contractors as $leadContractor) {
            if (CmsCompany2contractor::find()->andWhere(['cms_company_id' => $company->id, 'cms_contractor_id' => $leadContractor->cms_contractor_id])->exists()) {
                continue;
            }
            $map = new CmsCompany2contractor([
                'cms_company_id' => (int)$company->id,
                'cms_contractor_id' => (int)$leadContractor->cms_contractor_id,
            ]);
            if (!$map->save()) { throw new \RuntimeException(implode('; ', $map->getFirstErrors())); }
        }

        $log = new CmsLog();
        $log->log_type = CmsLog::LOG_TYPE_COMMENT;
        $log->model_code = $company->skeeksModelCode;
        $log->model_id = $company->id;
        $log->model_as_text = $company->asText;
        $log->cms_company_id = $company->id;
        $log->comment = 'Компания создана из лида №'.(int)$lead->id.' «'.Html::encode($lead->name).'».';
        if (!$log->save()) { throw new \RuntimeException(implode('; ', $log->getFirstErrors())); }
    }
}
