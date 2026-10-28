<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmMatcher;
use skeeks\cms\crm\LeadIdentityForm;
use skeeks\cms\crm\LeadTriage;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\yii2\form\fields\HtmlBlock;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;

/**
 * Duplicate check in the create forms of a company and a client (П2а): no
 * separate search — while the name, phone and email are filled, matches
 * across the base appear under them (own ones to open, others as "уже есть,
 * ответственный …"); a line pasted into the name («Ромашка, +7 …, info@…»)
 * is split into the fields. A new company gets its phone and email from the
 * fields of this block (CrmCardContacts).
 */
trait CrmDuplicateCheckActions
{
    /** @var \yii\db\Transaction|null the company with its phones and emails */
    private $_cardTransaction = null;

    public function duplicateCheckAction(): array
    {
        return [
            'class' => ViewBackendAction::class,
            'isVisible' => false,
            'callback' => [$this, 'duplicateCheck'],
        ];
    }

    public function duplicateRequestAction(): array
    {
        return [
            'class' => ViewBackendAction::class,
            'isVisible' => false,
            'callback' => [$this, 'duplicateRequest'],
        ];
    }

    /**
     * «Работать с этим клиентом» from a create form: a match with a client of
     * another main responsible, by the opaque reference of the match
     * (LeadTriage::workWithClient()).
     */
    public function duplicateRequest()
    {
        $response = new RequestResponse();
        if (!$response->isRequestAjaxPost()) {
            throw new BadRequestHttpException('Запрос отправляется из формы.');
        }
        $request = \Yii::$app->request;
        try {
            $result = (new LeadTriage())->workWithClient((string)$request->post('ref', ''), (int)\Yii::$app->user->id,
                trim((string)$request->post('comment', '')) ?: null);
            $response->success = true;
            $response->message = $result['message'];
            if ($result['lead'] && CrmAccess::instance()->canSeeLead((int)\Yii::$app->user->id, $result['lead'])) {
                $response->data = ['url' => Url::to(['/cms/admin-cms-lead/view', 'pk' => (int)$result['lead']->id])];
            }
        } catch (\DomainException $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }

        return $response;
    }

    /** The block after the name (company) or the phone (client) of a create form; $for — company or client. */
    public function duplicateCheckField(string $for): array
    {
        return [
            'class' => HtmlBlock::class,
            'content' => $this->renderPartial('@skeeks/cms/views/crm/_duplicate-check', [
                'for' => $for,
                'url' => Url::to(['/'.$this->getUniqueId().'/duplicate-check']),
            ]),
        ];
    }

    public function duplicateCheck()
    {
        if (!\Yii::$app->request->isAjax) {
            throw new BadRequestHttpException('Проверка загружается из формы.');
        }
        $query = trim((string)\Yii::$app->request->get('q', ''));
        $for = \Yii::$app->request->get('for') === CrmMatcher::TYPE_CLIENT ? CrmMatcher::TYPE_CLIENT : CrmMatcher::TYPE_COMPANY;
        if (mb_strlen($query) < 2) {
            return '';
        }
        $found = CrmMatcher::instance()->searchLine($query, $for, \Yii::$app->user->identity, (int)\Yii::$app->skeeks->site->id);

        return $this->renderPartial('@skeeks/cms/views/admin-cms-lead/_identity-results', [
            'candidates' => $found['candidates'],
            'for' => $for,
            'parsed' => $found['parsed'],
            'requestUrl' => Url::to(['/'.$this->getUniqueId().'/duplicate-request']),
        ]);
    }

    /** Phones and emails of the pasted line for a new company: checked before the save. */
    protected function cardContactErrors(): array
    {
        $data = (array)\Yii::$app->request->post('CrmCardContacts', []);
        $reason = trim((string)($data['duplicate_reason'] ?? ''));
        $errors = [];
        // The models check everything, a phone or an email of another company included (CrmDuplicateGuard):
        // a strong match is not saved without «Почему это другая компания».
        foreach (LeadIdentityForm::splitList($data['phones'] ?? '') as $phone) {
            $row = new CmsCompanyPhone(['value' => $phone, 'duplicate_reason' => $reason]);
            if (!$row->validate(['value', 'duplicate_reason'])) {
                $errors[] = implode(' ', $row->getErrors('value'));
            }
        }
        foreach (LeadIdentityForm::splitList($data['emails'] ?? '') as $email) {
            $row = new CmsCompanyEmail(['value' => mb_strtolower($email, 'UTF-8'), 'duplicate_reason' => $reason]);
            if (!$row->validate(['value', 'duplicate_reason'])) {
                $errors[] = implode(' ', $row->getErrors('value'));
            }
        }

        return $errors;
    }

    /** After the company is saved (same transaction as the form): a refusal rolls the company back. */
    protected function saveCardContacts(CmsCompany $company): void
    {
        $data = (array)\Yii::$app->request->post('CrmCardContacts', []);
        $reason = trim((string)($data['duplicate_reason'] ?? ''));
        $rows = [];
        foreach (LeadIdentityForm::splitList($data['phones'] ?? '') as $index => $phone) {
            $rows[] = new CmsCompanyPhone(['cms_company_id' => (int)$company->id, 'value' => $phone, 'sort' => 100 + $index, 'duplicate_reason' => $reason]);
        }
        foreach (LeadIdentityForm::splitList($data['emails'] ?? '') as $index => $email) {
            $rows[] = new CmsCompanyEmail(['cms_company_id' => (int)$company->id, 'value' => mb_strtolower($email, 'UTF-8'), 'sort' => 100 + $index, 'duplicate_reason' => $reason]);
        }
        foreach ($rows as $row) {
            if (!$row->save()) {
                throw new \yii\base\UserException(implode(' ', $row->getFirstErrors()));
            }
        }
    }
}
