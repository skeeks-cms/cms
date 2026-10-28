<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmContactParser;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\crm\CrmMatcher;
use skeeks\cms\crm\LeadIdentityForm;
use skeeks\cms\crm\LeadTriage;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use yii\base\Event;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\validators\Validator;
use yii\web\BadRequestHttpException;

/**
 * Sorting «Неразобранное», the client of a deal and assignment (П2а) in the
 * deals section. Decisions live in skeeks\cms\crm (CrmAccess, LeadTriage,
 * CrmMatcher; access requests — AdminCmsCrmAccessRequestController); these
 * actions parse requests and render.
 *
 * Forms post by AJAX and get a RequestResponse; inside a backend window the
 * page then closes it (model-update), otherwise it follows data.redirect.
 */
trait LeadTriageActions
{
    public function triageActions(): array
    {
        return [
            // The standard create window; the client block "Кто это" is part of its form
            // and the deal is saved by LeadTriage::createLead() with its client.
            'create' => [
                'class' => BackendModelCreateAction::class,
                'name' => 'Новая сделка',
                'priority' => 30,
                'fields' => [$this, 'createFields'],
                'activeFormConfig' => ['clientCallback' => $this->createFormCallback()],
                'on '.BackendModelCreateAction::EVENT_INIT_FORM_MODELS => function (Event $event) {
                    $event->sender->formModels['identity'] = $this->presetCreateCompany(new LeadIdentityForm(['executor_id' => (int)\Yii::$app->user->id]));
                },
                'on '.BackendModelCreateAction::EVENT_BEFORE_VALIDATE => function (Event $event) {
                    $this->checkCreateIdentity($event->sender);
                },
                'on '.BackendModelCreateAction::EVENT_BEFORE_SAVE => function (Event $event) {
                    $this->prepareCreate($event->sender);
                },
                'on '.BackendModelCreateAction::EVENT_AFTER_SAVE => function (Event $event) {
                    $this->finishCreate($event->sender);
                },
            ],
            'triage' => [
                'class' => BackendModelAction::class,
                'name' => 'Разобрать',
                'isVisible' => false,
                'callback' => [$this, 'triage'],
                'accessCallback' => fn() => $this->model && CrmAccess::instance()->canTriage((int)\Yii::$app->user->id, $this->model),
            ],
            'identity' => [
                'class' => BackendModelAction::class,
                'name' => 'Клиент сделки',
                'isVisible' => false,
                'callback' => [$this, 'identity'],
                'accessCallback' => fn() => $this->model && $this->model->status === CmsLead::STATUS_IN_WORK
                    && CrmAccess::instance()->canWorkLead((int)\Yii::$app->user->id, $this->model),
            ],
            'assign' => [
                'class' => BackendModelAction::class,
                'name' => 'Сменить ответственного',
                'isVisible' => false,
                'callback' => [$this, 'assign'],
                'accessCallback' => fn() => $this->model && CrmAccess::instance()->canAssignLead((int)\Yii::$app->user->id, $this->model),
            ],
            'copy-contact' => [
                'class' => BackendModelAction::class,
                'isVisible' => false,
                'callback' => [$this, 'copyContact'],
            ],
            'match' => [
                'class' => ViewBackendAction::class,
                'isVisible' => false,
                'callback' => [$this, 'match'],
            ],
            // «Работать с этим клиентом» for a client «Доступ только по согласованию»: the request goes first.
            'work-request' => [
                'class' => ViewBackendAction::class,
                'isVisible' => false,
                'callback' => [$this, 'workRequest'],
            ],
            // «Запросы доступа» is its own section (AdminCmsCrmAccessRequestController); old links lead there.
            'requests' => [
                'class' => ViewBackendAction::class,
                'isVisible' => false,
                'callback' => fn() => $this->redirect(['/cms/admin-cms-crm-access-request/index']),
            ],
        ];
    }

    /**
     * After «Новая сделка» is saved its card opens at once, as in Bitrix24:
     * in a backend window the window goes on to the card (the opener — the
     * kanban or a list — refreshes when it is closed), on a page the page
     * does; the filled form is never left to be saved twice. A deal that went
     * to the responsible of a foreign client (afterSaveUrl is not its card)
     * closes the window as usual, a page goes to the kanban. The error part is
     * the default one of ActiveFormAjaxBackend (crm-card-blocks.md, "A create
     * window that goes on to the next step").
     */
    protected function createFormCallback(): JsExpression
    {
        $card = Json::encode(Url::to(['/cms/admin-cms-lead/view']));

        return new JsExpression(<<<JS
function (form) {
    form.on('success', function (e, response) {
        var data = (response && response.data) || {};
        var url = data.afterSaveUrl || '';
        $('.sx-buttons-standart .sx-success-meessage', form.jForm).empty().append(response.message);
        form.jForm.removeClass('sx-form-data-changed');
        // Compared by the path: in a backend window the URLs carry its _sxb[...] parameters.
        var isCard = url !== '' && url.split('?')[0] === {$card}.split('?')[0];
        var inWindow = !!sx.Window.openerWidget();
        sx.Window.openerWidgetTriggerEvent('model-create', {'submitBtn': isCard ? 'apply' : 'save'});
        if (url && (isCard || !inWindow)) {
            // The window keeps its layout (_sxb[...]) on the card.
            var layout = window.location.search.replace(/^\?/, '').split('&').filter(function (pair) {
                return pair.indexOf('_sxb') === 0 && url.indexOf(pair) === -1;
            });
            window.location.href = url + (inWindow && layout.length ? (url.indexOf('?') === -1 ? '?' : '&') + layout.join('&') : '');
        }
    });
    form.on('error', function (e, response) {
        response = response || {};
        var messages = [];
        $.each((response.data && response.data.validation) || {}, function (attribute, errors) {
            $.each(Array.isArray(errors) ? errors : [errors], function (i, error) {
                if (typeof error === 'string' && error.trim() && messages.indexOf(error.trim()) === -1) {
                    messages.push(error.trim());
                }
            });
        });
        var message = messages.length ? messages.join(' ') : response.message;
        message = typeof message === 'string' && message.trim()
            ? 'Не удалось сохранить. ' + message.trim()
            : 'Не удалось сохранить данные. Попробуйте ещё раз.';
        $('.sx-buttons-standart .sx-success-meessage', form.jForm).empty().append($('<span>', {'class': 'sx-form-submit-error', 'role': 'alert'}).text(message));
    });
}
JS
        );
    }

    /** Fields of «Новая сделка»: the deal and the block "Кто это" (rendered _identity). */
    public function createFields($action): array
    {
        $identity = $action->formModels['identity'] ?? new LeadIdentityForm();
        // As in amoCRM and Bitrix24 the name may stay empty: «Компания — источник» or the number.
        $action->model->autoName = true;
        $dealFields = [
            'name' => ['elementOptions' => ['placeholder' => 'Можно не заполнять — подставим «Компания — источник»']],
        ];
        $dealFields += $this->createPipelineFields($action->model);
        if (CmsLead::amountReady()) {
            $dealFields['amount'] = ['class' => NumberField::class, 'elementOptions' => ['placeholder' => 'Ожидаемая сумма, ₽', 'min' => 0, 'step' => 'any']];
        }
        // As in Bitrix24: the name, the stage and the amount, then the client, the rest, and the responsible last.
        return [
            'main' => [
                'class' => FieldSet::class,
                'name' => 'Сделка',
                'fields' => $dealFields,
            ],
            'client' => [
                'class' => FieldSet::class,
                'name' => 'Клиент',
                'fields' => [
                    'identity' => [
                        'class' => HtmlBlock::class,
                        'content' => '<div class="sx-lead-identity-field">'.$this->renderPartial('@skeeks/cms/views/admin-cms-lead/_identity', [
                            'form' => $identity,
                            'lead' => $action->model,
                            'candidates' => [],
                            'allowForeign' => true,
                            'withExecutor' => false,
                        ]).'</div>',
                    ],
                ],
            ],
            'more' => [
                'class' => FieldSet::class,
                'name' => 'Подробнее',
                'fields' => [
                    'description' => ['class' => TextareaField::class],
                    'source_type' => ['class' => SelectField::class, 'items' => CmsLead::sources(), 'allowNull' => false],
                    'source_name' => [],
                ],
            ],
            'executor' => [
                'class' => FieldSet::class,
                'name' => 'Ответственный',
                'fields' => [
                    'executor' => [
                        'class' => HtmlBlock::class,
                        'content' => '<div class="sx-lead-identity-field">'.$this->renderPartial('@skeeks/cms/views/admin-cms-lead/_identity-executor', [
                            'form' => $identity,
                        ]).'</div>',
                    ],
                ],
            ],
        ];
    }

    /**
     * П2б: «Воронка» and «Этап» of a new deal, preset from the kanban column
     * where «+» was pressed (`pipeline_id`, `stage_id`). A site without
     * pipelines and stages gets no new fields. The stage applies when the deal
     * goes straight into work (it has a responsible); otherwise it lands in
     * «Неразобранное» of the pipeline.
     */
    protected function createPipelineFields(CmsLead $lead): array
    {
        if (!CmsLead::pipelineReady()) {
            return [];
        }
        $request = \Yii::$app->request;
        if (!$request->isPost) {
            $lead->cms_lead_pipeline_id = (int)$request->get('pipeline_id') ?: null;
            $lead->cms_lead_stage_id = (int)$request->get('stage_id') ?: null;
        }
        $fields = [];
        $pipelines = \skeeks\cms\models\CmsLeadPipeline::forSite();
        if (count($pipelines) > 1) {
            $fields['cms_lead_pipeline_id'] = [
                'class' => SelectField::class,
                'items' => \yii\helpers\ArrayHelper::map($pipelines, 'id', 'name'),
                'allowNull' => false,
            ];
        }
        $stages = \skeeks\cms\crm\LeadPipelines::instance()->stageItems();
        if ($stages) {
            $fields['cms_lead_stage_id'] = [
                'class' => SelectField::class,
                'items' => $stages,
                'nullLabel' => 'Первый этап воронки',
                'hint' => 'Если у сделки нет ответственного, она попадёт в «Неразобранное».',
            ];
        }

        return $fields;
    }

    /**
     * «Новая сделка» from the tab «Сделки» of a company card
     * (`CmsLead[cms_company_id]`, BackendGridModelRelatedAction): the company
     * is chosen in «Кто это» in advance, only if the employee sees it. The
     * posted form replaces it, and LeadTriage checks the choice as usual.
     */
    protected function presetCreateCompany(LeadIdentityForm $form): LeadIdentityForm
    {
        $preset = (array)\Yii::$app->request->get((new CmsLead())->formName());
        $companyId = (int)($preset['cms_company_id'] ?? 0);
        if ($companyId && CrmAccess::instance()->canSeeCompany((int)\Yii::$app->user->id, $companyId)) {
            $form->company_mode = LeadIdentityForm::MODE_EXISTING;
            $form->company_id = $companyId;
        }

        return $form;
    }

    /** @var array|null identity of the deal being created (LeadTriage::prepareNewLead()) */
    private ?array $_createIdentity = null;

    /** The whole creation (client, deal, record source) went through: commit. */
    private bool $_createFinished = false;

    /** The deal got no name from its client or source: it is named by its number after the save. */
    private bool $_createNumbered = false;

    /**
     * Before validation (and before a record source fills the deal): the
     * protected attributes are reset and the client choice is checked; a
     * refusal is shown as an error of the deal name.
     */
    protected function checkCreateIdentity(BackendModelCreateAction $action): void
    {
        /** @var CmsLead $lead */
        $lead = $action->model;
        $lead->cms_site_id = (int)\Yii::$app->skeeks->site->id;
        foreach (['submitted_by_id', 'partner_id', 'executor_id', 'cms_company_id', 'cms_user_id', 'source_ref', 'source_url', 'source_data',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'processed_at',
            'reject_reason', 'result_comment', 'cms_lead_reject_reason_id'] as $attribute) {
            $lead->{$attribute} = null;
        }
        // The stage of the form only on a site with pipelines: its rules check that it belongs to the pipeline.
        if (!CmsLead::pipelineReady()) {
            $lead->cms_lead_stage_id = null;
        }
        try {
            $triage = new LeadTriage();
            $triage->checkIdentity($action->formModels['identity'], (int)\Yii::$app->user->id);
            if (trim((string)$lead->name) === '') {
                $lead->name = $triage->suggestName($lead, $action->formModels['identity']);
                // Nothing to name it by: "Сделка №<id>" once it is saved.
                $this->_createNumbered = $lead->name === '';
                if ($this->_createNumbered) {
                    $lead->name = 'Новая сделка';
                }
            }
        } catch (\DomainException $e) {
            $message = $e->getMessage();
            // Not static: InlineValidator binds the closure to the model. The name may be empty
            // (it is filled automatically), so the refusal must not be skipped on an empty value.
            $lead->getValidators()->append(Validator::createValidator(function () use ($lead, $message) {
                $lead->addError('name', $message);
            }, $lead, ['name'], ['skipOnEmpty' => false]));
        }
    }

    /**
     * Before the standard save: one transaction for the client, the deal and
     * what a record source does after save; committed after the action,
     * rolled back if the request ends with an error.
     */
    protected function prepareCreate(BackendModelCreateAction $action): void
    {
        $db = CmsLead::getDb();
        $transaction = $db->beginTransaction();
        $this->_createFinished = false;
        // Committed only when finishCreate() ran: an error caught by the action (a PJAX form
        // shows it as a message) must not leave a company or a person without its deal.
        \Yii::$app->on(\yii\base\Application::EVENT_AFTER_ACTION, function () use ($transaction) {
            if ($transaction->isActive) {
                $this->_createFinished ? $transaction->commit() : $transaction->rollBack();
            }
        });
        \Yii::$app->on(\yii\base\Application::EVENT_AFTER_REQUEST, static function () use ($transaction) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
        });
        // Class-level handlers run after the instance ones and in the order attached: this one
        // comes after a record source's created() (RecordSources, attached at bootstrap).
        \yii\base\Event::on(BackendModelCreateAction::class, BackendModelCreateAction::EVENT_AFTER_SAVE, function ($event) use ($action) {
            if ($event->sender === $action) {
                $this->_createFinished = true;
            }
        });
        $this->_createIdentity = (new LeadTriage())->prepareNewLead($action->model, $action->formModels['identity'], (int)\Yii::$app->user->id);
    }

    protected function finishCreate(BackendModelCreateAction $action): void
    {
        if ($this->_createIdentity === null) {
            return;
        }
        $lead = $action->model;
        (new LeadTriage())->finishNewLead($lead, $this->_createIdentity, $action->formModels['identity'], (int)\Yii::$app->user->id);
        if ($this->_createNumbered) {
            $lead->updateAttributes(['name' => 'Сделка №'.(int)$lead->id]);
        }
        $action->afterSaveUrl = CrmAccess::instance()->canSeeLead((int)\Yii::$app->user->id, $lead)
            ? Url::to(['view', 'pk' => $lead->id])
            : Url::to(['kanban']);
    }

    public function triage()
    {
        $lead = $this->model;
        $actorId = (int)\Yii::$app->user->id;

        if (\Yii::$app->request->isPost) {
            $response = new RequestResponse();
            try {
                $triage = new LeadTriage();
                // The clicked button: "accept", "merge:<deal id>", "support", "spam".
                [$op, $opArgument] = array_pad(explode(':', (string)\Yii::$app->request->post('op'), 2), 2, null);
                $redirect = Url::to(['view', 'pk' => $lead->id]);
                switch ($op) {
                    case 'accept':
                        $form = new LeadIdentityForm();
                        $form->load(\Yii::$app->request->post());
                        $result = $triage->accept($lead, $form, $actorId, (int)\Yii::$app->request->post('stage_id') ?: null);
                        if (!CrmAccess::instance()->canSeeLead($actorId, $lead)) {
                            $redirect = Url::to(['kanban']);
                        }
                        break;
                    case 'merge':
                        $target = CmsLead::find()->forManager()->cmsSite()
                            ->andWhere([CmsLead::tableName().'.id' => (int)$opArgument])->one();
                        if (!$target) {
                            throw new \DomainException('Сделка не найдена или недоступна вам.');
                        }
                        $result = $triage->merge($lead, $target, $actorId);
                        $redirect = Url::to(['view', 'pk' => $target->id]);
                        break;
                    case 'support':
                        $result = $triage->support($lead, (int)\Yii::$app->request->post('deal_id') ?: null, $actorId);
                        break;
                    case 'spam':
                        $result = $triage->spam($lead, $actorId);
                        break;
                    default:
                        throw new BadRequestHttpException('Неизвестное действие.');
                }
                $this->respond($response, $result, $redirect);
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

        $matcher = CrmMatcher::instance();
        $raw = $matcher->rawFind($matcher->inputOfLead($lead));
        $viewer = \Yii::$app->user->identity;
        $repeatIds = $matcher->repeatsOf($raw, (int)$lead->cms_site_id, [(int)$lead->id]);
        $repeats = $repeatIds ? CmsLead::find()->forManager()->andWhere([CmsLead::tableName().'.id' => $repeatIds])
            ->with(['executor', 'stage'])->orderBy([CmsLead::tableName().'.id' => SORT_DESC])->all() : [];
        $hiddenRepeats = count($repeatIds) - count($repeats);

        $form = new LeadIdentityForm();
        if ($lead->cms_company_id) {
            $form->company_mode = LeadIdentityForm::MODE_NONE;
        }

        return $this->render('@skeeks/cms/views/admin-cms-lead/triage', [
            'model' => $lead,
            'form' => $form,
            'candidates' => $matcher->present($raw, $viewer, (int)$lead->cms_site_id),
            'repeats' => $repeats,
            'hiddenRepeats' => $hiddenRepeats,
            'deals' => $this->clientDeals($lead),
            'stageId' => (int)\Yii::$app->request->get('stage_id') ?: null,
        ]);
    }

    /** «Клиент сделки» for a deal in work: find or create its company and contact person. */
    public function identity()
    {
        $lead = $this->model;
        if (\Yii::$app->request->isPost) {
            $response = new RequestResponse();
            try {
                $form = new LeadIdentityForm();
                $form->load(\Yii::$app->request->post());
                $result = (new LeadTriage())->linkIdentity($lead, $form, (int)\Yii::$app->user->id);
                $this->respond($response, $result, Url::to(['view', 'pk' => $lead->id]));
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

        $matcher = CrmMatcher::instance();
        $raw = $matcher->rawFind($matcher->inputOfLead($lead));

        return $this->render('@skeeks/cms/views/admin-cms-lead/identity', [
            'model' => $lead,
            'form' => new LeadIdentityForm(),
            'candidates' => $matcher->present($raw, \Yii::$app->user->identity, (int)$lead->cms_site_id),
        ]);
    }

    /** «Поручить»: the deal to another employee, the company stays with its responsible. */
    public function assign()
    {
        $lead = $this->model;
        if (\Yii::$app->request->isPost) {
            $response = new RequestResponse();
            try {
                $result = (new LeadTriage())->assign(
                    $lead,
                    (int)\Yii::$app->request->post('executor_id'),
                    (int)\Yii::$app->user->id,
                    trim((string)\Yii::$app->request->post('comment')) ?: null
                );
                $this->respond($response, $result, Url::to(['view', 'pk' => $lead->id]));
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

        return $this->render('@skeeks/cms/views/admin-cms-lead/assign', ['model' => $lead]);
    }

    public function copyContact()
    {
        $response = new RequestResponse();
        if (!$response->isRequestAjaxPost()) {
            throw new BadRequestHttpException('Контакт переносится кнопкой из карточки сделки.');
        }
        try {
            $response->message = (new LeadTriage())->copyContact(
                $this->model,
                (string)\Yii::$app->request->post('kind'),
                (int)\Yii::$app->request->post('id'),
                (string)\Yii::$app->request->post('target'),
                (int)\Yii::$app->user->id
            );
            $response->success = true;
        } catch (\DomainException $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }

        return $response;
    }

    /** The client fields of the deal form while typing: the dropdown of a field or the check of a new record. */
    public function match()
    {
        if (!\Yii::$app->request->isAjax) {
            throw new BadRequestHttpException('Подсказки загружаются из формы сделки.');
        }
        $request = \Yii::$app->request;
        $query = trim((string)$request->get('q', ''));
        $for = $request->get('for') === CrmMatcher::TYPE_CLIENT ? CrmMatcher::TYPE_CLIENT : CrmMatcher::TYPE_COMPANY;
        if (mb_strlen($query) < 2) {
            return '';
        }
        $found = CrmMatcher::instance()->searchLine($query, $for, \Yii::$app->user->identity, (int)\Yii::$app->skeeks->site->id);
        $params = [
            'candidates' => $found['candidates'],
            'for' => $for,
            'parsed' => $found['parsed'],
            'query' => $query,
            'allowForeign' => (bool)$request->get('foreign', 1),
        ];

        // check — the phone, email and name of a new record (the popover «Есть совпадения»), otherwise the dropdown of the field.
        return $this->renderPartial($request->get('check') ? '@skeeks/cms/views/admin-cms-lead/_identity-check' : '@skeeks/cms/views/admin-cms-lead/_identity-dropdown', $params);
    }

    public function workRequest()
    {
        $response = new RequestResponse();
        if (!$response->isRequestAjaxPost()) {
            throw new BadRequestHttpException('Запрос отправляется из формы.');
        }
        try {
            $result = (new LeadTriage())->workWithClient((string)\Yii::$app->request->post('ref', ''), (int)\Yii::$app->user->id,
                trim((string)\Yii::$app->request->post('comment', '')) ?: null);
            $response->success = true;
            $response->message = $result['message'];
        } catch (\DomainException $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }

        return $response;
    }

    /** Services of the deal's client the employee sees: targets of «Это поддержка». */
    protected function clientDeals(CmsLead $lead): array
    {
        if (!$lead->cms_company_id && !$lead->cms_user_id) {
            return [];
        }
        $query = CmsDeal::find()->forManager()->limit(50)->orderBy([CmsDeal::tableName().'.is_active' => SORT_DESC, CmsDeal::tableName().'.id' => SORT_DESC]);
        $query->andWhere($lead->cms_company_id
            ? [CmsDeal::tableName().'.cms_company_id' => (int)$lead->cms_company_id]
            : [CmsDeal::tableName().'.cms_user_id' => (int)$lead->cms_user_id]);

        return $query->all();
    }

    private function respond(RequestResponse $response, array $result, string $redirect): void
    {
        $response->success = true;
        $response->message = (string)($result['message'] ?? 'Готово.');
        $response->data = ['redirect' => $redirect];
        \Yii::$app->session->setFlash('success', $response->message);
    }
}
