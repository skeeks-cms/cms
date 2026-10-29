<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsCrmDuplicateMark;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadEmail;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\CmsUserPhone;
use skeeks\cms\helpers\PhoneHelper;
use yii\db\StaleObjectException;
use yii\helpers\Html;

/**
 * Sorting of unsorted deals («Неразобранное») and the client of a deal (П2а).
 *
 * - accept(): take into work with a found or created company and contact
 *   person, or without a client; the responsible by CrmAccess::defaultExecutorId();
 * - merge(): the request continues an open deal — it is added there and
 *   closed (triage_result=merged, merged_into_id); repeated source events
 *   go to the target (CmsLead::resolveMerged());
 * - support(): a task on the client's service, the deal closes as support;
 * - spam(): the deal closes as spam;
 * - createLead(): a new deal from the deal form ("найти или создать");
 * - assign(): give a deal to another employee without the whole company.
 *
 * Every operation checks CrmAccess, runs in one transaction, writes the deal
 * activity and fires CmsLead::EVENT_TRIAGED after commit. Errors are
 * \DomainException with a message for the employee.
 */
class LeadTriage
{
    /** Application event after a deal is sorted (TriageEvent); configure 'on crm.leadTriaged'. */
    public const EVENT_LEAD_TRIAGED = 'crm.leadTriaged';

    /** A client of the general pool chosen by an employee without the pool right (CrmAccess::poolRequestNeeded()). */
    public const POOL_REQUEST_MESSAGE = 'Клиент в общей базе: взять его в работу можно с разрешения руководителя. '
        .'Нажмите «Попросить у руководителя» в подсказке: запрос уйдёт руководителю отдела, сделка будет создана после одобрения.';

    private CrmAccess $access;

    public function __construct(?CrmAccess $access = null)
    {
        $this->access = $access ?? CrmAccess::instance();
    }

    /**
     * Takes an unsorted deal into work.
     *
     * @return array lead, message, request (CmsCrmAccessRequest|null)
     */
    public function accept(CmsLead $lead, LeadIdentityForm $form, int $actorId, ?int $stageId = null): array
    {
        if (!$this->access->canTriage($actorId, $lead)) {
            throw new \DomainException('Эту сделку уже разобрали или она недоступна вам. Обновите страницу.');
        }
        $this->validate($form);
        if ($stageId && !CmsLeadStage::find()->cmsSite((int)$lead->cms_site_id)->andWhere(['id' => $stageId])->exists()) {
            throw new \DomainException('Этап не найден. Обновите страницу.');
        }

        return $this->transaction(function () use ($lead, $form, $actorId, $stageId) {
            // The request of a client does not wait for an approval (a client «по согласованию» goes to its main responsible).
            $identity = $this->applyIdentity($lead, $form, $actorId, true);
            $lead->executor_id = $identity['executorId'];
            $lead->status = CmsLead::STATUS_IN_WORK;
            if ($stageId) {
                $lead->cms_lead_stage_id = $stageId;
            }
            $this->markTriaged($lead, $actorId);
            $this->save($lead);
            $this->afterIdentity($lead, $identity, $form, $actorId);

            $parts = $this->identityText($lead);
            $lead->addSystemActivity('Сделка принята в работу'.($parts ? ': '.$parts : '').'. Ответственный — '
                .Html::encode($this->name((int)$lead->executor_id)).'.', $actorId);
            $this->notifyAssigned($lead, $actorId);

            return [
                'lead' => $lead,
                'request' => $identity['request'],
                'message' => $this->foreignMessage($lead, $identity, $actorId) ?: 'Сделка принята в работу.',
            ];
        }, function (array $result) use ($lead, $actorId) {
            $this->triggerTriaged($lead, 'accepted', $actorId);
        });
    }

    /**
     * A new deal from the deal form: the client is found or created in the
     * same transaction; without a responsible it lands in «Неразобранное».
     *
     * @param CmsLead $lead unsaved: name, description, source fields
     * @return array lead, message, request
     */
    public function createLead(CmsLead $lead, LeadIdentityForm $form, int $actorId): array
    {
        if (!$lead->isNewRecord) {
            throw new \DomainException('Сделка уже создана.');
        }
        $this->validate($form);

        $numbered = false;
        if (trim((string)$lead->name) === '') {
            $lead->name = $this->suggestName($lead, $form);
            $numbered = $lead->name === '';
            $lead->name = $numbered ? 'Новая сделка' : $lead->name;
        }

        return $this->transaction(function () use ($lead, $form, $actorId, $numbered) {
            $identity = $this->prepareNewLead($lead, $form, $actorId);
            $this->save($lead);
            if ($numbered) {
                $lead->updateAttributes(['name' => 'Сделка №'.(int)$lead->id]);
            }

            return $this->finishNewLead($lead, $identity, $form, $actorId);
        });
    }

    /**
     * «Работать с этим клиентом» outside the deal form (a create form of a
     * company or a client, a client «по согласованию» in the deal form) by the
     * opaque reference of a match: the deal is created for the actor with the
     * level "Свои сделки" and the main responsible is notified; for a client
     * «Доступ только по согласованию» only the request goes, the deal comes
     * with the approval.
     *
     * @return array lead (null while waiting for an approval), request, message
     */
    public function workWithClient(string $ref, int $actorId, ?string $comment = null): array
    {
        $parsed = $this->access->parseCandidateRef($ref);
        if (!$parsed || !in_array($parsed[0], [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT], true)) {
            throw new \DomainException('Совпадение устарело. Проверьте клиента ещё раз.');
        }
        $recordCode = $parsed[0] === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT;
        if ($this->access->poolRequestNeeded($actorId, $recordCode, (int)$parsed[1])) {
            // «Попросить у руководителя» (owner's decision 29.10.2026): the general pool without the pool right.
            $request = (new CrmAccessRequests($this->access))->request($actorId, $recordCode, (int)$parsed[1], CmsCrmAccessRequest::KIND_WORK, null, $comment, CrmAccess::LEVEL_FULL);

            return [
                'lead' => null,
                'request' => $request,
                'message' => 'Запрос отправлен руководителю. После одобрения вы станете основным ответственным, сделка будет создана на вас.',
            ];
        }
        if ($this->access->approvalRequired($recordCode, (int)$parsed[1])) {
            $request = (new CrmAccessRequests($this->access))->request($actorId, $recordCode, (int)$parsed[1], CmsCrmAccessRequest::KIND_WORK, null, $comment);

            return [
                'lead' => null,
                'request' => $request,
                'message' => 'С этим клиентом работают по согласованию: запрос отправлен основному ответственному, сделка будет создана после одобрения.',
            ];
        }
        $part = $parsed[0] === CrmMatcher::TYPE_COMPANY ? 'company' : 'client';
        $form = new LeadIdentityForm([$part.'_mode' => LeadIdentityForm::MODE_FOREIGN, $part.'_ref' => $ref, 'foreign_comment' => $comment]);
        $siteId = \Yii::$app->has('skeeks') && \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
        $result = $this->createLead(new CmsLead(['cms_site_id' => $siteId, 'description' => $comment]), $form, $actorId);
        $result['message'] = $this->access->mainResponsibleId($parsed[0] === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT, (int)$parsed[1]) === $actorId
            ? 'Клиент взят в работу: вы его основной ответственный, сделка №'.(int)$result['lead']->id.' создана на вас.'
            : 'Сделка №'.(int)$result['lead']->id.' создана на вас, у вас доступ «Свои сделки» к клиенту. Основной ответственный получил уведомление.';

        return $result;
    }

    /**
     * Checks the client choice without writing anything: hidden references,
     * visibility, the responsible. The deal form calls it before validation,
     * so a refusal is shown in the form instead of failing the save.
     */
    public function checkIdentity(LeadIdentityForm $form, int $actorId): void
    {
        $this->validate($form);
        if ($form->company_mode === LeadIdentityForm::MODE_EXISTING && !$this->access->canSeeCompany($actorId, (int)$form->company_id)) {
            throw new \DomainException('Компания не найдена или недоступна вам.');
        }
        if ($form->client_mode === LeadIdentityForm::MODE_EXISTING && !$this->access->canSeeClient($actorId, (int)$form->client_id)) {
            throw new \DomainException('Контакт не найден или недоступен вам.');
        }
        foreach (['company' => CrmMatcher::TYPE_COMPANY, 'client' => CrmMatcher::TYPE_CLIENT] as $part => $type) {
            if ($form->{$part.'_mode'} === LeadIdentityForm::MODE_FOREIGN) {
                $ref = $this->access->parseCandidateRef((string)$form->{$part.'_ref'});
                if (!$ref || $ref[0] !== $type) {
                    throw new \DomainException('Совпадение устарело. Проверьте клиента ещё раз.');
                }
                $recordCode = $type === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT;
                if ($this->access->poolRequestNeeded($actorId, $recordCode, (int)$ref[1])) {
                    throw new \DomainException(self::POOL_REQUEST_MESSAGE);
                }
                if ($this->access->approvalRequired($recordCode, (int)$ref[1])) {
                    throw new \DomainException('С этим клиентом работают только по согласованию с основным ответственным. '
                        .'Нажмите «Работать с этим клиентом» в подсказке: запрос уйдёт ответственному, сделка будет создана после одобрения.');
                }
            }
        }
        if ((int)$form->executor_id && !CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => (int)$form->executor_id, CmsUser::tableName().'.is_active' => 1])->exists()) {
            throw new \DomainException('Ответственный не найден.');
        }
        if ($form->company_mode === LeadIdentityForm::MODE_NEW) {
            foreach (LeadIdentityForm::splitList($form->company_phones) as $phone) {
                $row = new CmsCompanyPhone(['value' => $phone, 'duplicate_reason' => (string)$form->duplicate_reason]);
                if (!$row->validate(['value'])) {
                    throw new \DomainException('Телефон компании '.$phone.': '.implode(' ', $row->getErrors('value')));
                }
            }
            foreach (LeadIdentityForm::splitList($form->company_emails) as $email) {
                $row = new CmsCompanyEmail(['value' => mb_strtolower($email, 'UTF-8'), 'duplicate_reason' => (string)$form->duplicate_reason]);
                if (!$row->validate(['value'])) {
                    throw new \DomainException('Email компании '.$email.': '.implode(' ', $row->getErrors('value')));
                }
            }
        }
        // The contacts of a new person are checked by their own models now, not in the middle of the save.
        $siteId = \Yii::$app->has('skeeks') && \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
        foreach (['phone' => [CmsUserPhone::class, (string)$form->client_phone], 'email' => [CmsUserEmail::class, mb_strtolower((string)$form->client_email, 'UTF-8')]] as $kind => [$class, $value]) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            if ($form->client_mode === LeadIdentityForm::MODE_NEW) {
                $model = new $class(['value' => $value, 'cms_site_id' => $siteId]);
                if (!$model->validate(['value'])) {
                    throw new \DomainException(($kind === 'phone' ? 'Телефон: ' : 'Email: ').implode(' ', $model->getErrors('value')));
                }
            } elseif ($form->client_mode === LeadIdentityForm::MODE_NONE) {
                $model = $kind === 'phone' ? new CmsLeadPhone(['value' => $value]) : new CmsLeadEmail(['value' => $value]);
                if (!$model->validate(['value'])) {
                    throw new \DomainException(($kind === 'phone' ? 'Телефон: ' : 'Email: ').implode(' ', $model->getErrors('value')));
                }
            }
        }
    }

    /**
     * A name for a deal left without one (as in amoCRM and Bitrix24):
     * «<Компания или контакт> — <источник>»; '' when there is nothing to
     * name it by (the caller numbers it). The source is its own name, or its
     * kind unless the deal was made by hand.
     */
    public function suggestName(CmsLead $lead, LeadIdentityForm $form): string
    {
        $client = '';
        if ($form->company_mode === LeadIdentityForm::MODE_NEW) {
            $client = trim((string)$form->company_name);
        } elseif ($form->company_mode === LeadIdentityForm::MODE_EXISTING && ($company = CmsCompany::findOne((int)$form->company_id))) {
            $client = (string)$company->name;
        } elseif ($form->company_mode === LeadIdentityForm::MODE_FOREIGN
            && ($ref = $this->access->parseCandidateRef((string)$form->company_ref)) && ($company = CmsCompany::findOne($ref[1]))) {
            // A company name of a hidden match is shown to the employee anyway.
            $client = (string)$company->name;
        } elseif ($lead->cms_company_id && ($company = CmsCompany::findOne((int)$lead->cms_company_id))) {
            $client = (string)$company->name;
        }
        if ($client === '') {
            if ($form->client_mode === LeadIdentityForm::MODE_NEW) {
                $client = trim($form->client_first_name.' '.$form->client_last_name);
            } elseif ($form->client_mode === LeadIdentityForm::MODE_EXISTING && ($user = CmsUser::findOne((int)$form->client_id))) {
                $client = (string)$user->shortDisplayName;
            }
        }
        // П2д: a deal with «Услуга» is «Услуга — Компания» (outbound sales: the service is what tells deals apart).
        $service = $lead->cms_deal_type_id && CmsLead::dealTypeReady() && ($type = \skeeks\cms\models\CmsDealType::findOne((int)$lead->cms_deal_type_id))
            ? trim((string)$type->name) : '';
        if ($service !== '') {
            return mb_substr(implode(' — ', array_filter([$service, $client])), 0, 255);
        }
        $source = trim((string)$lead->source_name);
        if ($source === '' && $lead->source_type && $lead->source_type !== CmsLead::SOURCE_MANUAL) {
            $source = (string)(CmsLead::sources()[$lead->source_type] ?? '');
        }

        return $client === '' ? '' : mb_substr(implode(' — ', array_filter([$client, $source])), 0, 255);
    }

    /**
     * First half of a new deal (inside the caller's transaction, before the
     * deal is saved): the client is found or created, the responsible and the
     * status are set. A company or a person already set on the deal (a record
     * source, a letter) is kept.
     */
    public function prepareNewLead(CmsLead $lead, LeadIdentityForm $form, int $actorId): array
    {
        if ($lead->cms_company_id && $form->company_mode !== LeadIdentityForm::MODE_FOREIGN) {
            $form->company_mode = LeadIdentityForm::MODE_NONE;
        }
        if ($lead->cms_user_id && $form->client_mode !== LeadIdentityForm::MODE_FOREIGN) {
            $form->client_mode = LeadIdentityForm::MODE_NONE;
        }
        $identity = $this->applyIdentity($lead, $form, $actorId);
        $lead->executor_id = $identity['executorId'];
        $lead->status = $lead->executor_id ? CmsLead::STATUS_IN_WORK : CmsLead::STATUS_NEW;

        return $identity;
    }

    /** Second half of a new deal, after it is saved: its contacts, the request, the duplicate mark. */
    public function finishNewLead(CmsLead $lead, array $identity, LeadIdentityForm $form, int $actorId): array
    {
        if (!$identity['clientId'] && $form->client_mode === LeadIdentityForm::MODE_NONE) {
            // Without a contact person the typed contacts stay with the deal («Из обращения»).
            foreach (array_filter(['phone' => trim((string)$form->client_phone), 'email' => trim((string)$form->client_email)]) as $kind => $value) {
                $this->addLeadContact($lead, $kind, $value);
            }
        }
        $this->afterIdentity($lead, $identity, $form, $actorId);

        return [
            'lead' => $lead,
            'request' => $identity['request'],
            'message' => $this->foreignMessage($lead, $identity, $actorId) ?: 'Сделка создана.',
        ];
    }

    /**
     * The client of a deal in work ("Клиент сделки" in the card): links the
     * found or created company and contact person. Never replaces a
     * different client already linked.
     */
    public function linkIdentity(CmsLead $lead, LeadIdentityForm $form, int $actorId): array
    {
        if (!$this->access->canWorkLead($actorId, $lead) || $lead->status !== CmsLead::STATUS_IN_WORK) {
            throw new \DomainException('Клиента можно указать только у сделки в работе, закреплённой за вами.');
        }
        if ($form->isForeign()) {
            throw new \DomainException('Этот клиент закреплён за другим сотрудником. Запросите доступ из окна совпадения.');
        }
        $this->validate($form);
        $executorId = (int)$lead->executor_id;

        return $this->transaction(function () use ($lead, $form, $actorId, $executorId) {
            $identity = $this->applyIdentity($lead, $form, $actorId);
            // The responsible stays: linking a client does not move the deal.
            $lead->executor_id = $executorId;
            $this->save($lead);
            $this->afterIdentity($lead, $identity, $form, $actorId);
            $parts = $this->identityText($lead);
            if ($parts) {
                $lead->addSystemActivity('Указан клиент сделки: '.$parts.'.', $actorId);
            }

            return ['lead' => $lead, 'request' => null, 'message' => 'Клиент сделки сохранён.'];
        });
    }

    /** The request continues an open deal: added there, this deal closes. */
    public function merge(CmsLead $lead, CmsLead $target, int $actorId): array
    {
        if (!$this->access->canTriage($actorId, $lead)) {
            throw new \DomainException('Эту сделку уже разобрали или она недоступна вам. Обновите страницу.');
        }
        if ((int)$target->id === (int)$lead->id || (int)$target->cms_site_id !== (int)$lead->cms_site_id
            || !in_array($target->status, [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK], true)
            || !$this->access->canSeeLead($actorId, $target)) {
            throw new \DomainException('Выберите открытую сделку, доступную вам.');
        }
        if ($lead->partner_id && (int)$lead->partner_id !== (int)$target->partner_id) {
            // Otherwise the partner reward of this request would be lost or go to another partner.
            throw new \DomainException('Заявку партнёра можно добавить только в сделку того же партнёра. Примите её в работу отдельно.');
        }

        return $this->transaction(function () use ($lead, $target, $actorId) {
            foreach ($lead->phones as $phone) {
                $this->addLeadContact($target, 'phone', (string)$phone->value, $phone->name);
            }
            foreach ($lead->emails as $email) {
                $this->addLeadContact($target, 'email', (string)$email->value, $email->name);
            }
            CmsTask::updateAll(['cms_lead_id' => (int)$target->id], [
                'cms_lead_id' => (int)$lead->id,
                'status' => CmsTask::activeStatuses(),
            ]);

            $lead->executor_id = $lead->executor_id ?: ($target->executor_id ?: $actorId);
            $lead->merged_into_id = (int)$target->id;
            $this->close($lead, CmsLead::TRIAGE_MERGED, $actorId);

            $excerpt = trim(mb_substr(trim((string)$lead->description), 0, 500));
            $target->addSystemActivity('Добавлено обращение '.Html::a('№'.(int)$lead->id, ['/cms/admin-cms-lead/view', 'pk' => (int)$lead->id], ['data-pjax' => 0])
                .' «'.Html::encode((string)$lead->name).'» ('.Html::encode($lead->sourceNameAsText).')'
                .($excerpt !== '' ? ':<br>'.nl2br(Html::encode($excerpt)) : '.'), $actorId);
            $lead->addSystemActivity('Обращение добавлено в сделку '.Html::a('№'.(int)$target->id, ['/cms/admin-cms-lead/view', 'pk' => (int)$target->id], ['data-pjax' => 0])
                .' «'.Html::encode((string)$target->name).'».', $actorId);

            if ($target->executor_id && (int)$target->executor_id !== $actorId) {
                \Yii::$app->notifications->notify((int)$target->executor_id, 'lead.merged', [
                    'name' => 'В сделку добавлено обращение',
                    'comment' => (string)$lead->name,
                    'model' => $target,
                    'eventKey' => 'lead.merged:'.(int)$lead->id,
                ]);
            }

            return ['lead' => $lead, 'target' => $target, 'request' => null, 'message' => 'Обращение добавлено в сделку №'.(int)$target->id.'.'];
        }, function () use ($lead, $target, $actorId) {
            $this->triggerTriaged($lead, CmsLead::TRIAGE_MERGED, $actorId, $target);
        });
    }

    /** A request about a working service: a support task, the deal closes. */
    public function support(CmsLead $lead, ?int $dealId, int $actorId): array
    {
        if (!$this->access->canTriage($actorId, $lead)) {
            throw new \DomainException('Эту сделку уже разобрали или она недоступна вам. Обновите страницу.');
        }
        $deal = null;
        if ($dealId) {
            $deal = CmsDeal::find()->forManager(CmsUser::findOne($actorId))->andWhere([CmsDeal::tableName().'.id' => $dealId])->one();
            if (!$deal) {
                throw new \DomainException('Услуга не найдена или недоступна вам.');
            }
        }

        $task = null;
        $result = $this->transaction(function () use ($lead, $deal, $actorId, &$task) {
            $task = new CmsTask();
            $task->name = mb_substr('Поддержка: '.$lead->displayName, 0, 255);
            $task->executor_id = $actorId;
            $task->cms_lead_id = (int)$lead->id;
            if ($deal) {
                $task->cms_deal_id = (int)$deal->id;
            } elseif ($lead->cms_company_id) {
                $task->cms_company_id = (int)$lead->cms_company_id;
            } elseif ($lead->cms_user_id) {
                $task->cms_user_id = (int)$lead->cms_user_id;
            }
            $description = trim((string)$lead->description);
            $contacts = array_merge(
                array_map(static fn($phone) => 'Телефон: '.$phone->value, $lead->phones),
                array_map(static fn($email) => 'Email: '.$email->value, $lead->emails)
            );
            $task->description = nl2br(Html::encode(trim($description."\n\n".implode("\n", $contacts))));
            if (!$task->save()) {
                throw new \DomainException('Не удалось поставить задачу: '.implode('; ', $task->getFirstErrors()));
            }

            $lead->executor_id = $lead->executor_id ?: $actorId;
            $this->close($lead, CmsLead::TRIAGE_SUPPORT, $actorId);
            $lead->addSystemActivity('Обращение передано в поддержку: задача '.Html::a('№'.(int)$task->id, ['/cms/admin-cms-task/view', 'pk' => (int)$task->id], ['data-pjax' => 0])
                .' «'.Html::encode((string)$task->name).'»'.($deal ? ' по услуге «'.Html::encode((string)$deal->asText).'»' : '').'.', $actorId);

            return ['lead' => $lead, 'task' => $task, 'request' => null, 'message' => 'Поставлена задача поддержки №'.(int)$task->id.'.'];
        }, function () use ($lead, $actorId, &$task) {
            $this->triggerTriaged($lead, CmsLead::TRIAGE_SUPPORT, $actorId, null, $task);
        });

        return $result;
    }

    public function spam(CmsLead $lead, int $actorId): array
    {
        if (!$this->access->canTriage($actorId, $lead)) {
            throw new \DomainException('Эту сделку уже разобрали или она недоступна вам. Обновите страницу.');
        }

        return $this->transaction(function () use ($lead, $actorId) {
            $lead->executor_id = $lead->executor_id ?: $actorId;
            $this->close($lead, CmsLead::TRIAGE_SPAM, $actorId);
            $lead->addSystemActivity('Сделка отмечена как спам.', $actorId);

            return ['lead' => $lead, 'request' => null, 'message' => 'Сделка отмечена как спам.'];
        }, function () use ($lead, $actorId) {
            $this->triggerTriaged($lead, CmsLead::TRIAGE_SPAM, $actorId);
        });
    }

    /**
     * Gives the deal to another employee ("поручить"). The company stays with
     * its responsible: the new one sees it only as "название + ответственный".
     */
    public function assign(CmsLead $lead, int $executorId, int $actorId, ?string $comment = null): array
    {
        if (!$this->access->canAssignLead($actorId, $lead)) {
            throw new \DomainException('Сменить ответственного может ответственный, его руководитель, ответственный клиента или администратор.');
        }
        $executor = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $executorId, CmsUser::tableName().'.is_active' => 1])->one();
        if (!$executor) {
            throw new \DomainException('Сотрудник не найден.');
        }
        if ((int)$lead->executor_id === $executorId && $lead->status !== CmsLead::STATUS_NEW) {
            return ['lead' => $lead, 'request' => null, 'message' => 'Сделка уже у этого сотрудника.'];
        }

        return $this->transaction(function () use ($lead, $executor, $actorId, $comment) {
            $lead->executor_id = (int)$executor->id;
            if ($lead->status === CmsLead::STATUS_NEW) {
                $lead->status = CmsLead::STATUS_IN_WORK;
                $this->markTriaged($lead, $actorId);
            }
            $this->save($lead);
            $lead->addSystemActivity('Ответственный сделки — '.Html::encode((string)$executor->shortDisplayName)
                .($comment ? ': '.Html::encode($comment) : '.'), $actorId);
            $this->notifyAssigned($lead, $actorId);
            $this->notifyDelegated($lead, $actorId);

            return ['lead' => $lead, 'request' => null, 'message' => 'Ответственный сделки — '.$executor->shortDisplayName.'.'];
        });
    }

    /** A contact of the request copied to the card of the deal's company or person by choice. */
    public function copyContact(CmsLead $lead, string $kind, int $contactId, string $target, int $actorId): string
    {
        if (!$this->access->canWorkLead($actorId, $lead)) {
            throw new \DomainException('Переносить контакты может ответственный за сделку.');
        }
        $model = $kind === 'phone'
            ? CmsLeadPhone::findOne(['id' => $contactId, 'cms_lead_id' => (int)$lead->id])
            : CmsLeadEmail::findOne(['id' => $contactId, 'cms_lead_id' => (int)$lead->id]);
        if (!$model) {
            throw new \DomainException('Контакт не найден.');
        }
        if (!$this->mayCopyTo($lead, $target, null, $actorId)) {
            throw new \DomainException('Карточка клиента недоступна вам.');
        }
        $added = $this->transaction(function () use ($lead, $kind, $model, $target, $actorId) {
            $added = $this->copyToCard($lead, $kind, (string)$model->value, $model->name, $target);
            if ($added) {
                $lead->addSystemActivity(($kind === 'phone' ? 'Телефон ' : 'Email ').Html::encode((string)$model->value)
                    .' добавлен в карточку '.($target === 'company' ? 'компании' : 'контакта').'.', $actorId);
            }

            return $added;
        });

        return $added ? 'Контакт добавлен в карточку.' : 'Такой контакт уже есть в карточке.';
    }

    /**
     * «Работать с этим клиентом»: what happened to a deal of a client of another main responsible.
     */
    private function foreignMessage(CmsLead $lead, array $identity, int $actorId): string
    {
        if (!$identity['foreign']) {
            return '';
        }
        if ((int)$lead->executor_id === $actorId) {
            return 'Сделка ваша: у вас доступ «Свои сделки» к клиенту, основному ответственному отправлено уведомление.';
        }

        return 'Клиент с доступом по согласованию: сделка у основного ответственного '.$this->name((int)$lead->executor_id)
            .($identity['request'] ? ', запрос работать с клиентом отправлен.' : '.');
    }

    /**
     * Applies the client of the form to the deal (not saved yet).
     *
     * A client of another main responsible («Работать с этим клиентом»): the
     * deal is the actor's and he gets the level "Свои сделки" (afterIdentity());
     * a client «Доступ только по согласованию» is refused for a deal made by
     * hand (a request goes first, the deal is created after the approval), an
     * incoming deal ($incoming) goes to its main responsible with the request.
     *
     * @return array companyId, clientId, executorId, foreign ([type, id]|null), request (filled later),
     *               newCompany, newClient, duplicate ([type, id]|null)
     */
    public function applyIdentity(CmsLead $lead, LeadIdentityForm $form, int $actorId, bool $incoming = false): array
    {
        $companyId = $lead->cms_company_id ? (int)$lead->cms_company_id : null;
        $clientId = $lead->cms_user_id ? (int)$lead->cms_user_id : null;
        $foreign = null;
        $newCompany = null;
        $newClient = null;

        if ($form->company_mode !== LeadIdentityForm::MODE_NONE && $companyId) {
            throw new \DomainException('С этой сделкой уже связана компания.');
        }
        if ($form->client_mode !== LeadIdentityForm::MODE_NONE && $clientId) {
            throw new \DomainException('С этой сделкой уже связан контакт.');
        }

        switch ($form->company_mode) {
            case LeadIdentityForm::MODE_EXISTING:
                if (!$this->access->canSeeCompany($actorId, (int)$form->company_id)) {
                    throw new \DomainException('Компания не найдена или недоступна вам.');
                }
                $companyId = (int)$form->company_id;
                break;
            case LeadIdentityForm::MODE_FOREIGN:
                $ref = $this->access->parseCandidateRef((string)$form->company_ref);
                if (!$ref || $ref[0] !== CrmMatcher::TYPE_COMPANY || !CmsCompany::find()->andWhere(['id' => $ref[1]])->exists()) {
                    throw new \DomainException('Совпадение устарело. Проверьте клиента ещё раз.');
                }
                $companyId = $ref[1];
                $foreign = [CmsCrmAccessRequest::RECORD_COMPANY, $companyId];
                break;
        }
        switch ($form->client_mode) {
            case LeadIdentityForm::MODE_EXISTING:
                if (!$this->access->canSeeClient($actorId, (int)$form->client_id)) {
                    throw new \DomainException('Контакт не найден или недоступен вам.');
                }
                $clientId = (int)$form->client_id;
                break;
            case LeadIdentityForm::MODE_FOREIGN:
                $ref = $this->access->parseCandidateRef((string)$form->client_ref);
                if (!$ref || $ref[0] !== CrmMatcher::TYPE_CLIENT || !CmsUser::find()->andWhere(['id' => $ref[1], 'is_worker' => 0])->exists()) {
                    throw new \DomainException('Совпадение устарело. Проверьте клиента ещё раз.');
                }
                $clientId = $ref[1];
                $foreign = $foreign ?? [CmsCrmAccessRequest::RECORD_CLIENT, $clientId];
                break;
        }

        if ($foreign && !$incoming && $this->access->poolRequestNeeded($actorId, $foreign[0], (int)$foreign[1])) {
            throw new \DomainException(self::POOL_REQUEST_MESSAGE);
        }
        if ($foreign && $this->access->approvalRequired($foreign[0], (int)$foreign[1])) {
            if (!$incoming) {
                throw new \DomainException('С этим клиентом работают только по согласованию с основным ответственным. '
                    .'Нажмите «Работать с этим клиентом» в подсказке: запрос уйдёт ответственному, сделка будет создана после одобрения.');
            }
            // An incoming request does not wait: the deal goes to the main responsible, the actor asks to work.
            $executorId = $this->access->mainResponsibleId($foreign[0], (int)$foreign[1]) ?: $actorId;
        } elseif ($foreign) {
            // «Работать с этим клиентом»: the deal is the actor's, the level "Свои сделки" is given after the save.
            $executorId = $actorId;
        } else {
            $executorId = (int)$form->executor_id ?: $this->access->defaultExecutorId($companyId, $clientId, $actorId);
            if ((int)$form->executor_id && !CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => (int)$form->executor_id, CmsUser::tableName().'.is_active' => 1])->exists()) {
                throw new \DomainException('Ответственный не найден.');
            }
        }

        if ($form->company_mode === LeadIdentityForm::MODE_NEW) {
            $company = new CmsCompany(['name' => trim((string)$form->company_name)]);
            if (!$company->save()) {
                throw new \DomainException('Не удалось создать компанию: '.implode('; ', $company->getFirstErrors()));
            }
            $this->addCompanyManager((int)$company->id, (int)($executorId ?: $actorId));
            // Phones and emails of a pasted line go to the new company card.
            foreach (LeadIdentityForm::splitList($form->company_phones) as $index => $phone) {
                $row = new CmsCompanyPhone(['cms_company_id' => (int)$company->id, 'value' => $phone, 'sort' => 100 + $index, 'duplicate_reason' => (string)$form->duplicate_reason]);
                if (!$row->save()) {
                    throw new \DomainException('Телефон компании: '.implode('; ', $row->getFirstErrors()));
                }
            }
            foreach (LeadIdentityForm::splitList($form->company_emails) as $index => $email) {
                $row = new CmsCompanyEmail(['cms_company_id' => (int)$company->id, 'value' => mb_strtolower($email, 'UTF-8'), 'sort' => 100 + $index, 'duplicate_reason' => (string)$form->duplicate_reason]);
                if (!$row->save()) {
                    throw new \DomainException('Email компании: '.implode('; ', $row->getFirstErrors()));
                }
            }
            // П2д: the site of a company found by a manager goes to its links.
            if (($site = LeadIdentityForm::siteUrl($form->company_site)) !== '') {
                $row = new \skeeks\cms\models\CmsCompanyLink(['cms_company_id' => (int)$company->id, 'url' => $site, 'name' => 'Сайт']);
                if (!$row->save()) {
                    throw new \DomainException('Сайт компании: '.implode('; ', $row->getFirstErrors()));
                }
            }
            $companyId = (int)$company->id;
            $newCompany = $company;
        }
        if ($form->client_mode === LeadIdentityForm::MODE_NEW) {
            $client = new CmsUser([
                'first_name' => trim((string)$form->client_first_name),
                'last_name' => trim((string)$form->client_last_name) ?: null,
                'is_worker' => 0,
            ]);
            if ($lead->cms_site_id) {
                $client->cms_site_id = (int)$lead->cms_site_id;
            }
            if (!$client->save()) {
                throw new \DomainException('Не удалось создать контакт: '.implode('; ', $client->getFirstErrors()));
            }
            if (trim((string)$form->client_phone) !== '') {
                $this->addClientContact($client, 'phone', (string)$form->client_phone);
            }
            if (trim((string)$form->client_email) !== '') {
                $this->addClientContact($client, 'email', (string)$form->client_email);
            }
            $manager = new CmsUser2manager(['client_id' => (int)$client->id, 'worker_id' => (int)($executorId ?: $actorId)]);
            if (!$manager->save()) {
                throw new \DomainException('Не удалось закрепить контакт: '.implode('; ', $manager->getFirstErrors()));
            }
            $clientId = (int)$client->id;
            $newClient = $client;
        }

        // A contact person of the deal's company is linked to it (also an existing one the actor sees);
        // П2а+: people of an existing company are its data — "Свои сделки" does not add them.
        if ($companyId && $clientId && !$foreign
            && ($newCompany || $this->access->canEditClientData($actorId, CmsCrmAccessRequest::RECORD_COMPANY, $companyId))
            && !CmsCompany2user::find()->andWhere(['cms_company_id' => $companyId, 'cms_user_id' => $clientId])->exists()) {
            $link = new CmsCompany2user(['cms_company_id' => $companyId, 'cms_user_id' => $clientId]);
            if (!$link->save()) {
                throw new \DomainException('Не удалось связать контакт с компанией: '.implode('; ', $link->getFirstErrors()));
            }
        }

        $duplicate = null;
        if (trim((string)$form->duplicate_ref) !== '') {
            $duplicate = $this->access->parseCandidateRef((string)$form->duplicate_ref);
            if (!$duplicate || $duplicate[0] === CrmMatcher::TYPE_LEAD) {
                $duplicate = null;
            }
        }

        $lead->cms_company_id = $companyId;
        $lead->cms_user_id = $clientId;

        return [
            'companyId' => $companyId,
            'clientId' => $clientId,
            'executorId' => $executorId ?: null,
            'foreign' => $foreign,
            'request' => null,
            'newCompany' => $newCompany,
            'newClient' => $newClient,
            'duplicate' => $duplicate,
        ];
    }

    /** After the deal is saved: contacts by choice, the access request, the duplicate mark. */
    private function afterIdentity(CmsLead $lead, array &$identity, LeadIdentityForm $form, int $actorId): void
    {
        foreach ((array)$form->contacts as $key => $target) {
            if (!in_array($target, ['company', 'client'], true) || $identity['foreign']) {
                continue;
            }
            [$kind, $id] = array_pad(explode(':', (string)$key, 2), 2, 0);
            $model = $kind === 'phone'
                ? CmsLeadPhone::findOne(['id' => (int)$id, 'cms_lead_id' => (int)$lead->id])
                : ($kind === 'email' ? CmsLeadEmail::findOne(['id' => (int)$id, 'cms_lead_id' => (int)$lead->id]) : null);
            if ($model && $this->mayCopyTo($lead, $target, $identity, $actorId)) {
                $this->copyToCard($lead, $kind, (string)$model->value, $model->name, $target);
            }
        }

        if ($identity['foreign']) {
            [$recordCode, $recordId] = [$identity['foreign'][0], (int)$identity['foreign'][1]];
            if ((int)$lead->executor_id === $actorId && $this->access->mainResponsibleId($recordCode, $recordId) === null
                && \skeeks\cms\crm\CrmAccess::hasHistory($recordCode, $recordId, (int)$lead->id)) {
                // A client with a history and still without a main one: the main one by the history first
                // (owner's decision 29.10.2026), the actor works on his deal below.
                (new CrmProtection())->assignHistoryMain($recordCode, $recordId);
            }
            if ((int)$lead->executor_id === $actorId && $this->access->poolRequestNeeded($actorId, $recordCode, $recordId)) {
                // An incoming deal of a pool client sorted by an employee without the pool right: the deal is his,
                // the client is asked from his head (owner's decision 29.10.2026).
                $identity['request'] = (new CrmAccessRequests($this->access))->request(
                    $actorId, $recordCode, $recordId, CmsCrmAccessRequest::KIND_WORK, (int)$lead->id, $form->foreign_comment, CrmAccess::LEVEL_FULL
                );
            } elseif ((int)$lead->executor_id === $actorId && $this->access->mainResponsibleId($recordCode, $recordId) === null) {
                // «Взять в работу» (П2д): a free client of the general pool — the actor becomes its main
                // responsible ("full"), no request and no notification; the next one gets «Работать с этим клиентом».
                $this->access->setMain($recordCode, $recordId, $actorId);
                CrmAccessLog::write(CrmAccessLog::TAKE, $recordCode, $recordId,
                    ['actor_id' => $actorId, 'user_id' => $actorId, 'level_to' => CrmAccess::LEVEL_FULL, 'cms_lead_id' => (int)$lead->id]);
            } elseif ((int)$lead->executor_id === $actorId) {
                // «Работать с этим клиентом»: the deal is the actor's, the level "Свои сделки" at once, no request
                // (owner's decision 28.10.2026): the main responsible and his leaders are notified; the level is
                // raised in «Доступ» of the client.
                $levelBefore = $this->access->linkLevel($actorId, $recordCode, $recordId);
                $this->access->grantLink($recordCode, $recordId, $actorId, CrmAccess::LEVEL_DEAL);
                if ($levelBefore === null) {
                    CrmAccessLog::write(CrmAccessLog::WORK, $recordCode, $recordId,
                        ['actor_id' => $actorId, 'user_id' => $actorId, 'level_to' => CrmAccess::LEVEL_DEAL, 'cms_lead_id' => (int)$lead->id]);
                }
                $this->notifyWorkWithClient($lead, $recordCode, $recordId, $actorId);
            } else {
                // An incoming request of a client «по согласованию»: the deal went to the main responsible, the actor asks.
                $identity['request'] = (new CrmAccessRequests($this->access))->request(
                    $actorId, $recordCode, $recordId, CmsCrmAccessRequest::KIND_WORK, (int)$lead->id, $form->foreign_comment
                );
            }
        }

        $new = $identity['newCompany']
            ? [CmsCrmAccessRequest::RECORD_COMPANY, (int)$identity['newCompany']->id]
            : ($identity['newClient'] ? [CmsCrmAccessRequest::RECORD_CLIENT, (int)$identity['newClient']->id] : null);
        if ($identity['duplicate'] && $new) {
            $this->markDuplicate($new, $identity['duplicate'], (string)$form->duplicate_reason, $actorId);
        }
    }

    /**
     * One rule for every way a contact of the request goes to a client card:
     * the card must be the deal's company or person, created by the actor in
     * this very operation or one he sees: a new phone or email is added on
     * every level (П2а+, CrmAccess::canAddClientContact()); existing ones are
     * never changed here.
     */
    private function mayCopyTo(CmsLead $lead, string $target, ?array $identity, int $actorId): bool
    {
        if ($target === 'company') {
            if (!$lead->cms_company_id) {
                return false;
            }
            $created = $identity && $identity['newCompany'] && (int)$identity['newCompany']->id === (int)$lead->cms_company_id;

            return $created || $this->access->canAddClientContact($actorId, [CmsCrmAccessRequest::RECORD_COMPANY, (int)$lead->cms_company_id], (int)$lead->id);
        }
        if ($target === 'client') {
            if (!$lead->cms_user_id) {
                return false;
            }
            $created = $identity && $identity['newClient'] && (int)$identity['newClient']->id === (int)$lead->cms_user_id;

            return $created || $this->access->canAddClientContact($actorId, [CmsCrmAccessRequest::RECORD_CLIENT, (int)$lead->cms_user_id], (int)$lead->id);
        }

        return false;
    }

    /** "Это другая компания": the pair is kept for an administrator, nothing is merged. */
    public function markDuplicate(array $record, array $other, string $reason, int $actorId): CmsCrmDuplicateMark
    {
        // The same pair may already be marked by the phone or email check (CrmDuplicateGuard).
        $existing = CmsCrmDuplicateMark::findOne(['record_code' => $record[0], 'record_id' => (int)$record[1], 'other_code' => $other[0], 'other_id' => (int)$other[1]]);
        if ($existing) {
            return $existing;
        }
        $mark = new CmsCrmDuplicateMark([
            'record_code' => $record[0],
            'record_id' => (int)$record[1],
            'other_code' => $other[0],
            'other_id' => (int)$other[1],
            'reason' => $reason,
        ]);
        if (!$mark->save()) {
            throw new \DomainException('Не удалось сохранить отметку: '.implode('; ', $mark->getFirstErrors()));
        }
        $adminIds = array_values(array_diff($this->access->adminIds(), [$actorId]));
        if ($adminIds) {
            $record = $record[0] === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne((int)$record[1]) : CmsUser::findOne((int)$record[1]);
            \Yii::$app->notifications->notify($adminIds, 'crm.duplicate_mark', [
                'name' => 'Возможный дубль клиента',
                'comment' => $this->name($actorId).': «'.($record ? $record->asText : '').'» — '.$reason,
                'model' => $record,
                'eventKey' => 'crm.duplicate_mark:'.(int)$mark->id,
            ]);
        }

        return $mark;
    }

    private function validate(LeadIdentityForm $form): void
    {
        if (!$form->validate()) {
            throw new \DomainException(implode(' ', $form->getFirstErrors()));
        }
    }

    private function close(CmsLead $lead, string $result, int $actorId): void
    {
        if (!CmsLead::triageReady()) {
            throw new \DomainException('Обновление базы ещё не применено. Выполните миграции.');
        }
        $lead->triage_result = $result;
        $lead->status = CmsLead::STATUS_REJECTED;
        $lead->processed_at = time();
        $this->markTriaged($lead, $actorId);
        $this->save($lead);
    }

    private function markTriaged(CmsLead $lead, int $actorId): void
    {
        if (CmsLead::triageReady()) {
            $lead->triaged_by = $actorId;
            $lead->triaged_at = time();
        }
    }

    private function save(CmsLead $lead): void
    {
        try {
            if (!$lead->save()) {
                throw new \DomainException(implode(' ', $lead->getFirstErrors()));
            }
        } catch (StaleObjectException $e) {
            throw new \DomainException('Сделку уже изменили в другой вкладке. Обновите страницу.', 0, $e);
        }
    }

    private function addLeadContact(CmsLead $lead, string $kind, string $value, ?string $name = null): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        if ($kind === 'phone') {
            $condition = PhoneHelper::equalCondition('value', $value) ?: ['value' => $value];
            if (CmsLeadPhone::find()->andWhere(['cms_lead_id' => (int)$lead->id])->andWhere($condition)->exists()) {
                return;
            }
            $model = new CmsLeadPhone(['cms_lead_id' => (int)$lead->id, 'value' => $value, 'name' => $name]);
        } else {
            $value = mb_strtolower($value, 'UTF-8');
            if (CmsLeadEmail::find()->andWhere(['cms_lead_id' => (int)$lead->id, 'value' => $value])->exists()) {
                return;
            }
            $model = new CmsLeadEmail(['cms_lead_id' => (int)$lead->id, 'value' => $value, 'name' => $name]);
        }
        if (!$model->save()) {
            throw new \DomainException('Не удалось сохранить контакт сделки: '.implode('; ', $model->getFirstErrors()));
        }
    }

    private function addClientContact(CmsUser $client, string $kind, string $value): void
    {
        $model = $kind === 'phone'
            ? new CmsUserPhone(['cms_user_id' => (int)$client->id, 'value' => trim($value)])
            : new CmsUserEmail(['cms_user_id' => (int)$client->id, 'value' => mb_strtolower(trim($value), 'UTF-8')]);
        $model->cms_site_id = (int)$client->cms_site_id;
        if (!$model->save()) {
            throw new \DomainException(($kind === 'phone' ? 'Телефон: ' : 'Email: ').implode('; ', $model->getFirstErrors()));
        }
    }

    /** @return bool false when the card already has it */
    private function copyToCard(CmsLead $lead, string $kind, string $value, ?string $name, string $target): bool
    {
        if ($target === 'company') {
            $class = $kind === 'phone' ? CmsCompanyPhone::class : CmsCompanyEmail::class;
            $owner = ['cms_company_id' => (int)$lead->cms_company_id];
        } else {
            $class = $kind === 'phone' ? CmsUserPhone::class : CmsUserEmail::class;
            $owner = ['cms_user_id' => (int)$lead->cms_user_id];
        }
        $query = $class::find()->andWhere($owner);
        $query->andWhere($kind === 'phone' ? (PhoneHelper::equalCondition('value', $value) ?: ['value' => $value]) : ['value' => mb_strtolower($value, 'UTF-8')]);
        if ($query->exists()) {
            return false;
        }
        $model = new $class($owner + ['value' => $kind === 'email' ? mb_strtolower($value, 'UTF-8') : $value]);
        if ($name !== null && $model->hasAttribute('name')) {
            $model->name = $name;
        }
        if ($target === 'client') {
            $model->cms_site_id = (int)CmsUser::find()->select('cms_site_id')->andWhere(['id' => (int)$lead->cms_user_id])->scalar();
        }
        if (!$model->save()) {
            throw new \DomainException('Не удалось добавить контакт в карточку: '.implode('; ', $model->getFirstErrors()));
        }

        return true;
    }

    private function addCompanyManager(int $companyId, int $userId): void
    {
        if (CmsCompany2manager::find()->andWhere(['cms_company_id' => $companyId, 'cms_user_id' => $userId])->exists()) {
            return;
        }
        $manager = new CmsCompany2manager(['cms_company_id' => $companyId, 'cms_user_id' => $userId]);
        if (!$manager->save()) {
            throw new \DomainException('Не удалось закрепить компанию: '.implode('; ', $manager->getFirstErrors()));
        }
    }

    private function notifyAssigned(CmsLead $lead, int $actorId): void
    {
        if ($lead->executor_id && (int)$lead->executor_id !== $actorId) {
            \Yii::$app->notifications->notify((int)$lead->executor_id, 'lead.assigned', [
                'name' => 'Вам назначена сделка',
                'comment' => (string)$lead->name,
                'model' => $lead,
                'eventKey' => 'lead.assigned:'.(int)$lead->id.':'.(int)$lead->executor_id,
            ]);
        }
    }

    /**
     * «Работать с этим клиентом»: the main responsible of the client and his
     * leaders learn that another employee works with the client on his deal
     * (the client in the text, the deal as the link; no grammatical gender).
     */
    private function notifyWorkWithClient(CmsLead $lead, string $recordCode, int $recordId, int $actorId): void
    {
        $mainId = $this->access->mainResponsibleId($recordCode, $recordId);
        if (!$mainId) {
            return;
        }
        $recipients = array_values(array_diff(array_unique(array_merge([$mainId], CmsDepartment::leaderIdsOf($mainId))), [$actorId]));
        if (!$recipients) {
            return;
        }
        $record = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne($recordId) : CmsUser::findOne($recordId);
        $client = $record instanceof CmsCompany ? (string)$record->name : ($record ? (string)$record->shortDisplayName : '№'.$recordId);
        \Yii::$app->notifications->notify($recipients, 'lead.delegated', [
            'name' => $this->name($actorId).': работа с клиентом «'.$client.'» по своей сделке',
            'comment' => (string)$lead->name,
            'model' => $lead,
            'eventKey' => 'lead.delegated:'.(int)$lead->id.':'.(int)$lead->executor_id,
        ]);
    }

    /** The responsible of the company learns that another employee leads its deal. */
    private function notifyDelegated(CmsLead $lead, int $actorId): void
    {
        if (!$lead->cms_company_id || !$lead->executor_id) {
            return;
        }
        $owners = $this->access->companyResponsibleIds((int)$lead->cms_company_id);
        if (in_array((int)$lead->executor_id, $owners, true)) {
            return;
        }
        $recipients = array_values(array_diff($owners, [$actorId]));
        if ($recipients) {
            \Yii::$app->notifications->notify($recipients, 'lead.delegated', [
                'name' => 'Сделку по вашему клиенту ведёт '.$this->name((int)$lead->executor_id),
                'comment' => (string)$lead->name,
                'model' => $lead,
                'eventKey' => 'lead.delegated:'.(int)$lead->id.':'.(int)$lead->executor_id,
            ]);
        }
    }

    private function identityText(CmsLead $lead): string
    {
        $parts = [];
        if ($lead->cms_company_id && ($company = CmsCompany::findOne((int)$lead->cms_company_id))) {
            $parts[] = 'компания «'.Html::encode((string)$company->name).'»';
        }
        if ($lead->cms_user_id && ($client = CmsUser::findOne((int)$lead->cms_user_id))) {
            $parts[] = 'контакт «'.Html::encode((string)$client->shortDisplayName).'»';
        }

        return implode(', ', $parts);
    }

    private function name(int $userId): string
    {
        $user = $userId ? CmsUser::findOne($userId) : null;

        return $user ? (string)$user->shortDisplayName : '—';
    }

    private function triggerTriaged(CmsLead $lead, string $result, int $actorId, ?CmsLead $target = null, ?CmsTask $task = null): void
    {
        $config = [
            'lead' => $lead,
            'result' => $result,
            'target' => $target,
            'task' => $task,
            'actorId' => $actorId,
        ];
        $lead->trigger(CmsLead::EVENT_TRIAGED, new TriageEvent($config));
        // Packages bind it in their config ('on crm.leadTriaged'), so jobs and consoles get it too.
        \Yii::$app->trigger(self::EVENT_LEAD_TRIAGED, new TriageEvent($config));
    }

    /**
     * Runs $work in a transaction (joins an active one); $afterCommit gets the
     * result once the outermost transaction of this call is committed.
     */
    private function transaction(callable $work, ?callable $afterCommit = null)
    {
        $db = CmsLead::getDb();
        $own = !$db->getTransaction() || !$db->getTransaction()->isActive;
        $transaction = $own ? $db->beginTransaction() : null;
        try {
            $result = $work();
            if ($transaction) {
                $transaction->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction && $transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
        if ($afterCommit) {
            $afterCommit($result);
        }

        return $result;
    }
}
