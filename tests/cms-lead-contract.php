<?php

$root = dirname(__DIR__).'/src';
$model = file_get_contents($root.'/models/CmsLead.php');
$controller = file_get_contents($root.'/controllers/AdminCmsLeadController.php');
$menu = file_get_contents($root.'/config/admin/menu.php');
$permissions = file_get_contents($root.'/config/_permissions.php');
$form2Boundary = file_get_contents($root.'/migrations/m260818_130000__create_table__cms_lead.php');
$service = file_get_contents($root.'/services/CmsLeadService.php');
$view = file_get_contents($root.'/views/admin-cms-lead/view.php');
$header = file_get_contents($root.'/views/admin-cms-lead/_model_header.php');
$statusWidget = file_get_contents($root.'/widgets/admin/CmsLeadStatusWidget.php');
$phoneModel = file_get_contents($root.'/models/CmsLeadPhone.php');
$emailModel = file_get_contents($root.'/models/CmsLeadEmail.php');
$companyController = file_get_contents($root.'/controllers/AdminCmsCompanyController.php');
$userController = file_get_contents($root.'/controllers/AdminUserController.php');

$requiredModelFragments = [
    "[['source_type', 'status'], 'required']",
    "[['name'], 'required', 'when' => static fn(self \$model) => !\$model->autoName]",
    'public function getPhones()',
    'public function getEmails()',
    'public function getMainPhone()',
    'public function getMainEmail()',
    "self::SOURCE_PARTNER => 'Партнёрская программа'",
    'public function allowedNextStatuses(): array',
    'self::STATUS_NEW => $this->getTriageResult()',
    'self::STATUS_IN_WORK => [self::STATUS_IN_WORK, self::STATUS_SUCCESS, self::STATUS_REJECTED]',
    'self::STATUS_SUCCESS => [self::STATUS_SUCCESS, self::STATUS_IN_WORK]',
    'self::STATUS_REJECTED => [self::STATUS_REJECTED, self::STATUS_IN_WORK]',
    'public function canBeReopenedBy(int $userId): bool',
    'notifyAvailableManagers()',
    'notifyPartnerAboutComment(?int $logId = null)',
    'getPartnerViewUrl(?int $logId = null)',
    "['sx-log-id' => \$logId]",
    "'#sx-log-'.\$logId",
    'notifyManagersAboutPartnerComment()',
    "'Вам назначена новая сделка'",
    "'attribute_value_maps'",
    "'status' => self::statuses()",
    "'source_type' => self::sources()",
    '$this->source_type = self::SOURCE_MANUAL',
    '$this->status = self::STATUS_NEW',
];
foreach ($requiredModelFragments as $fragment) {
    if (strpos($model, $fragment) === false) {
        throw new RuntimeException('Missing CmsLead contract: '.$fragment);
    }
}
$adminFallbackStart = strpos($model, 'public function availableManagerIds()');
$adminFallbackEnd = strpos($model, 'return array_values(array_unique($userIds));', $adminFallbackStart);
$adminFallback = $adminFallbackStart === false || $adminFallbackEnd === false
    ? ''
    : substr($model, $adminFallbackStart, $adminFallbackEnd - $adminFallbackStart);
foreach (['isWorker()', ".'.is_active' => 1", 'self::find()->forManager($worker)', "checkAccess(\$worker->id, 'cms/admin-lead')"] as $fragment) {
    if (strpos($adminFallback, $fragment) === false) {
        throw new RuntimeException('Known leads without an eligible manager must fall back to active administrators: '.$fragment);
    }
}
foreach (['sx-status', 'sx-status--accent', 'sx-status--success', 'sx-status--danger'] as $statusClass) {
    if (strpos($model, $statusClass) === false) {
        throw new RuntimeException('Lead status must use the shared semantic status contract: '.$statusClass);
    }
}
foreach (['CmsLead::statusCssClass', 'CmsLead::statusIconClass', 'BackendAsset::register'] as $widgetContract) {
    if (strpos($statusWidget, $widgetContract) === false) {
        throw new RuntimeException('Lead status widget is incomplete: '.$widgetContract);
    }
}

// Action priorities, as declared in actions(): list actions and the card head keep their own
// numbers; the card tabs take theirs from crm\CrmCardTabs, one order for every CRM card.
preg_match_all('/^ {4}const (PRIORITY_[A-Z_]+) = (\d+);/m', file_get_contents($root.'/crm/CrmCardTabs.php'), $tabConstants);
$tabPriorities = array_combine($tabConstants[1], array_map('intval', $tabConstants[2]));
preg_match_all("/^ {12}'([a-z-]+)' => \[(.*?)^ {12}\],/ms", $controller, $actionBlocks, PREG_SET_ORDER);
$actionPriorities = [];
foreach ($actionBlocks as [, $actionId, $actionBody]) {
    if (!preg_match("/^ {16}'priority' => ([^,]+),/m", $actionBody, $p)) {
        continue;
    }
    $expression = trim($p[1]);
    if (preg_match('/^\d+$/', $expression)) {
        $actionPriorities[$actionId] = ['literal', (int)$expression];
    } elseif (preg_match('/^\\\\skeeks\\\\cms\\\\crm\\\\CrmCardTabs::(PRIORITY_[A-Z_]+)$/', $expression, $c) && isset($tabPriorities[$c[1]])) {
        $actionPriorities[$actionId] = ['tab', $tabPriorities[$c[1]]];
    } else {
        throw new RuntimeException('Unknown lead action priority of '.$actionId.': '.$expression);
    }
}
foreach (['kanban' => 10, 'index' => 20, 'view' => 10, 'process' => 20] as $actionId => $priority) {
    if (($actionPriorities[$actionId] ?? null) !== ['literal', $priority]) {
        throw new RuntimeException('Missing lead action priority: '.$actionId.' => '.$priority);
    }
}
$cardTabsInOrder = ['deals', 'bills', 'payments', 'documents', 'tasks', 'stage-history', 'edit', 'log'];
$previousTab = null;
foreach ($cardTabsInOrder as $actionId) {
    if (($actionPriorities[$actionId][0] ?? null) !== 'tab') {
        throw new RuntimeException('Lead-card tab must take its priority from CrmCardTabs: '.$actionId);
    }
    if ($actionPriorities[$actionId][1] <= $actionPriorities['process'][1]
        || ($previousTab !== null && $actionPriorities[$actionId][1] <= $actionPriorities[$previousTab][1])) {
        throw new RuntimeException('Lead-card tabs are out of the shared CrmCardTabs order at '.$actionId);
    }
    $previousTab = $actionId;
}
$editActionStart = strpos($controller, "'edit' => [");
$editActionEnd = strpos($controller, "'log' => [", $editActionStart);
$editAction = $editActionStart === false || $editActionEnd === false
    ? ''
    : substr($controller, $editActionStart, $editActionEnd - $editActionStart);
if (strpos($editAction, "'icon' => 'fa fa-edit'") === false) {
    throw new RuntimeException('The dedicated lead edit action must use an available backend icon.');
}
if (strpos($controller, "'visibleFilters' => ['q', 'status', 'source_type']") === false) {
    // П2б: «Воронка» joins the filters only on a site with several pipelines.
    if (strpos($controller, "['cms_lead_stage_id', 'cms_lead_reject_reason_id', 'next_step', 'source_type'], \$utmAttributes)") === false
        || strpos($controller, "count(CmsLeadPipeline::forSite()) > 1 ? ['cms_lead_pipeline_id'] : []") === false) {
        throw new RuntimeException('Lead filters must include source and canonical UTM attribution fields.');
    }
}
if (strpos($controller, "'update' => new UnsetArrayValue()") === false) {
    throw new RuntimeException('The inherited update action must not duplicate the dedicated lead edit action.');
}
// The reject text label depends on whether the site uses a reason list.
foreach (["'label' => 'Результат обработки'", "'Причина отклонения'", "'Комментарий к отказу'"] as $label) {
    if (strpos($controller, $label) === false) {
        throw new RuntimeException('Lead process fields must have Russian labels: '.$label);
    }
}
if (strpos($controller, "\$fields['partner_id'] = \$this->partnerField()") === false) {
    throw new RuntimeException('Lead create/edit fields must expose the partner selector.');
}
$processFieldsStart = strpos($controller, 'public function processFields');
$statusOptionsStart = strpos($controller, 'private function statusFieldOptions');
$processFields = substr($controller, $processFieldsStart, $statusOptionsStart - $processFieldsStart);
if (strpos($processFields, 'executor_id') !== false || strpos($processFields, 'managerField') !== false) {
    throw new RuntimeException('Lead processing must not reassign the responsible manager.');
}
// П2а: «Новая сделка» is the standard create window with the client block; the responsible
// defaults to the current employee (or the client's responsible) and a deal with one is in work.
$triageActions = file_get_contents(dirname(__DIR__).'/src/controllers/traits/LeadTriageActions.php');
$triage = file_get_contents(dirname(__DIR__).'/src/crm/LeadTriage.php');
if (strpos($triageActions, "new LeadIdentityForm(['executor_id' => (int)\\Yii::\$app->user->id])") === false
    || strpos($triageActions, "'class' => BackendModelCreateAction::class") === false
    || strpos($triage, '$lead->status = $lead->executor_id ? CmsLead::STATUS_IN_WORK : CmsLead::STATUS_NEW') === false
    || strpos($controller, "\$fields['executor_id'] = \$this->managerField()") === false
    || strpos($controller, "'label' => 'Ответственный сотрудник'") === false
) {
    throw new RuntimeException('Manual lead creation must default to the current manager, allow reassignment, and set in-work status.');
}
if (strpos($controller, 'notifyPartnerAboutComment((int)$log->id)') === false) {
    throw new RuntimeException('Partner comment notifications must deep-link to the created log entry.');
}
foreach (["self::STATUS_NEW => 'Новый'", "self::STATUS_SUCCESS => 'Успешный'", "self::STATUS_REJECTED => 'Отклонён'"] as $status) {
    if (strpos($model, $status) === false) {
        throw new RuntimeException('Lead status must agree with the masculine noun: '.$status);
    }
}

$company = strpos($menu, "'label'    => \\Yii::t('skeeks/cms', 'Компании')");
$client = strpos($menu, "'label'    => \\Yii::t('skeeks/cms', 'Клиенты')");
$lead = strpos($menu, "'label'    => \\Yii::t('skeeks/cms', 'Сделки')");
if ($company === false || $client === false || $lead === false) {
    throw new RuntimeException('CRM menu entries are incomplete.');
}
// The menu is sorted by priority, not by the order in the file.
if (!preg_match("/'Сделки'\)[\\s\\S]*?'priority'\s*=>\s*100/", $menu)
    || !preg_match("/'Компании'\)[\\s\\S]*?'priority'\s*=>\s*110/", $menu)
    || !preg_match("/'Клиенты'\)[\\s\\S]*?'priority'\s*=>\s*120/", $menu)
) {
    throw new RuntimeException('CRM menu order must be Leads, Companies, Clients.');
}
if (!preg_match("/'name'\s*=>\s*'cms\/admin-lead'[\s\S]*?'description'\s*=>\s*\['skeeks\/cms',\s*'Сделки'\]/", $permissions)) {
    throw new RuntimeException('Lead section must expose the same explicit RBAC permission contract as companies and clients.');
}
if (strpos($form2Boundary, "'cms_site_id'") === false || strpos($form2Boundary, "'source_type'") === false) {
    throw new RuntimeException('CmsLead migration must retain transitional site scope and source metadata.');
}
if (strpos($service, 'catch (IntegrityException $e)') === false
    || strpos($service, 'fromSource($sourceType, $sourceRef, $siteId)') === false
) {
    throw new RuntimeException('Lead source ingestion must remain idempotent under concurrent inserts.');
}
if (strpos($service, 'beginTransactionIfNeeded') === false
    || strpos($service, '$db->getTransaction()') === false
) {
    throw new RuntimeException('Lead ingestion must join an existing source-adapter transaction.');
}
foreach (['normalizeUtmAttributes', 'parse_url($sourceUrl, PHP_URL_QUERY)', 'CmsLead::UTM_ATTRIBUTES'] as $utmContract) {
    if (strpos($service, $utmContract) === false) {
        throw new RuntimeException('Lead ingestion must normalize UTM attribution: '.$utmContract);
    }
}
foreach (CmsLeadUtmContract::ATTRIBUTES as $attribute) {
    if (strpos($form2Boundary, "'{$attribute}'") === false
        || strpos($form2Boundary, "'cms_lead__'.\$column") === false
        || strpos($controller, "'defaultMode' => FilterModeEq::ID") === false
    ) {
        throw new RuntimeException('Lead UTM field must be stored, indexed and filterable: '.$attribute);
    }
}
if (strpos($view, "'/form2/admin-form-send'") === false
    || strpos($view, "'Отправка №'.\$formSendId") === false
    || strpos($view, "'fas fa-envelope-open-text'") === false
) {
    throw new RuntimeException('A Form2 lead card must expose its source submission as a dedicated entity card.');
}
foreach (['Источник и атрибуция', 'CmsLead::utmLabels()', 'Страница обращения', 'Предыдущая страница'] as $attributionFragment) {
    if (strpos($view, $attributionFragment) === false) {
        throw new RuntimeException('Lead attribution surface is incomplete: '.$attributionFragment);
    }
}
if (strpos($view, "'title' => \$model->name") !== false
    || strpos($view, "'hint' => 'Лид №'") !== false
    || strpos($view, '<span class="sx-collection-cell__secondary">Отправка формы</span>') !== false
) {
    throw new RuntimeException('Lead description must not repeat the header or embed the source-submission link.');
}
if (strpos($view, 'min-height: 220px') !== false) {
    throw new RuntimeException('Lead card must not retain the old artificial minimum height.');
}
// П2б: the comment form is «Примечание», as in amoCRM.
// The note form is the block of card actions (CrmCardActionsWidget::leadConfig()), shared with the company card.
foreach (['sx-detail-layout__aside', 'sx-detail-layout__main', 'CrmCardActionsWidget::widget(\skeeks\cms\widgets\admin\CrmCardActionsWidget::leadConfig($model))', '$model->getLogs()->activity()'] as $activityFragment) {
    if (strpos($view, $activityFragment) === false) {
        throw new RuntimeException('Lead card must use the company-style split activity layout: '.$activityFragment);
    }
}
$cardActions = file_get_contents($root.'/widgets/admin/CrmCardActionsWidget.php');
if (strpos($cardActions, "'commentUrl' => \$canWork ? ['/cms/admin-cms-lead/add-comment', 'pk' => \$lead->id] : null") === false
    || strpos($cardActions, "'noteLabel' => \$lead->partner_id ? 'Партнёру' : 'Примечание'") === false) {
    throw new RuntimeException('The lead card note must be «Примечание», posted to add-comment only by who works with the deal.');
}
$matchesPosition = strpos($view, 'class="sx-lead-matches-slot"');
$detailLayoutPosition = strpos($view, 'class="sx-lead-layout sx-detail-layout"');
if ($matchesPosition === false || $detailLayoutPosition === false || $matchesPosition > $detailLayoutPosition) {
    throw new RuntimeException('CRM matches must span the full lead-card width before the detail columns.');
}
if (strpos($view, 'sx-lead-matches-surface') === false
    || strpos($view, 'var(--sx-color-success-soft)') === false
) {
    throw new RuntimeException('Found CRM matches must use the semantic success surface.');
}
if (strpos($view, "'title' => 'Обработка лида'") !== false
    || strpos($view, '<span class="sx-properties--name">Добавлен</span>') !== false
) {
    throw new RuntimeException('Lead status, owner and date must not be duplicated in the card body.');
}
foreach (["'Компания'", "'Клиент'", "'Партнёр'"] as $identityLabel) {
    if (strpos($view, $identityLabel) === false) {
        throw new RuntimeException('Lead relation must have a dedicated entity card: '.$identityLabel);
    }
}
foreach (['sx-surface--raised sx-lead-identity-card', 'sx-lead-identity-card__icon', 'sx-lead-identity-card__label', 'sx-lead-identity-card__title', 'sx-lead-identity-card__meta'] as $identityCardFragment) {
    if (strpos($view, $identityCardFragment) === false) {
        throw new RuntimeException('Lead relation entity card is incomplete: '.$identityCardFragment);
    }
}
$leadAsidePosition = strpos($view, '<aside class="sx-detail-layout__aside');
$leadMainPosition = strpos($view, '<main class="sx-detail-layout__main');
if ($leadAsidePosition === false || $leadMainPosition === false || $leadAsidePosition > $leadMainPosition) {
    throw new RuntimeException('Lead details must stay in the narrow left column and activity in the wide right column.');
}
if (strpos($view, 'getLogs()->comments()') !== false) {
    throw new RuntimeException('Lead activity must not hide telephony calls behind a comments-only filter.');
}
foreach (['CmsLeadPhone', 'cms_lead_id', 'validateUniquePhone', 'PhoneValidator::class'] as $fragment) {
    if (strpos($phoneModel, $fragment) === false) {
        throw new RuntimeException('Lead phone model is incomplete: '.$fragment);
    }
}
foreach (['CmsLeadEmail', 'cms_lead_id', "'enableIDN' => true"] as $fragment) {
    if (strpos($emailModel, $fragment) === false) {
        throw new RuntimeException('Lead email model is incomplete: '.$fragment);
    }
}
foreach (["createContactTable('cms_lead_phone'", "createContactTable('cms_lead_email'", "dropContactTable('cms_lead_email'", "dropContactTable('cms_lead_phone'"] as $fragment) {
    if (strpos($form2Boundary, $fragment) === false) {
        throw new RuntimeException('Initial lead contact schema is incomplete: '.$fragment);
    }
}
foreach (['sx-send-sms-trigger', 'sx-telephony-btn', '/cms/admin-cms-lead-phone', '/cms/admin-cms-lead-email', 'Указать клиента'] as $fragment) {
    if (strpos($view, $fragment) === false) {
        throw new RuntimeException('Lead contact surface is incomplete: '.$fragment);
    }
}
// П2г: the request data are read only — no «Добавить …» of the deal's own phones, emails, addresses.
foreach (['/cms/admin-cms-lead-phone/create', '/cms/admin-cms-lead-email/create', '/cms/admin-cms-lead-address/create',
    '/cms/admin-cms-lead-contractor/create', '/cms/admin-cms-lead-link/create'] as $fragment) {
    if (strpos($view, $fragment) !== false) {
        throw new RuntimeException('The deal card must not add contacts to the deal: '.$fragment);
    }
}
$contactActions = file_get_contents($root.'/controllers/traits/LeadContactActions.php');
foreach (['Phone' => 'BackendAction::SIZE_SMALL, true', 'Email' => 'BackendAction::SIZE_SMALL, true', 'Address' => 'null, true', 'Link' => 'BackendAction::SIZE_SMALL, true'] as $kind => $call) {
    if (strpos(file_get_contents($root.'/controllers/AdminCmsLead'.$kind.'Controller.php'), 'leadContactActions('.$call.')') === false) {
        throw new RuntimeException('Lead '.$kind.' rows must be request data (create/update refused).');
    }
}
if (strpos(file_get_contents($root.'/controllers/AdminCmsLeadContractorController.php'), "\$actions['create']['accessCallback'] = static fn() => false;") === false) {
    throw new RuntimeException('Requisites of a lead are request data: create is refused.');
}
if (strpos($contactActions, '$write = $requestData ? static fn() => false : $access;') === false) {
    throw new RuntimeException('Request data must refuse create and update for everybody.');
}
if (strpos($controller, "@skeeks/cms/views/admin-cms-lead/_model_header") === false) {
    throw new RuntimeException('Lead workflow actions must be available from the shared model header on every lead action.');
}
// п. 13б: an unsorted deal is sorted by «Взять в работу» and «Не продажа» (was «Разобрать»).
foreach (['Взять в работу', 'Не продажа', '/cms/admin-cms-lead/triage', 'Клиент сделки', 'Сменить ответственного', 'BackendModelHeader::widget', 'CmsLeadStatusWidget::widget'] as $headerFragment) {
    if (strpos($header, $headerFragment) === false) {
        throw new RuntimeException('Lead model header is incomplete: '.$headerFragment);
    }
}
foreach (['Вернуть в работу', '/cms/admin-cms-lead/reopen', 'canBeReopenedBy'] as $reopenFragment) {
    if (strpos($controller.$header, $reopenFragment) === false) {
        throw new RuntimeException('A completed lead must expose an explicit return-to-work action: '.$reopenFragment);
    }
}
foreach (['$this->model->status = CmsLead::STATUS_IN_WORK', '$this->model->processed_at = null'] as $reopenContract) {
    if (strpos($controller, $reopenContract) === false) {
        throw new RuntimeException('Returning a lead to work must restore its active lifecycle state: '.$reopenContract);
    }
}
// Working actions go through the single rule CrmAccess::canWorkLead(), which keeps completed leads read-only.
$crmAccess = file_get_contents($root.'/crm/CrmAccess.php');
if (strpos($controller, 'CrmAccess::instance()->canWorkLead((int)\Yii::$app->user->id, $this->model)') === false
    || strpos($crmAccess, 'return !$lead->isTerminal && $this->isLeadWorker($user, $lead);') === false
    || strpos(file_get_contents($root.'/models/CmsLead.php'), 'CrmAccess::instance()->canWorkLead($userId, $this)') === false
) {
    throw new RuntimeException('Completed leads must remain read-only in controller actions through CrmAccess::canWorkLead().');
}
// П2г: the request data have no add routes at all; row menus and call/SMS actions only when $canWork.
if (strpos($view, "'addRoute' => \$canWork ?") !== false
    || strpos($view, 'string $title): string => $canWork') === false
    || strpos($view, '(string)$phone->value, $model, $canWork ? [') === false
    || strpos($view, '$canWork = $model->canBeWorkedBy') === false
) {
    throw new RuntimeException('Completed lead contact controls must remain behind the shared work-access predicate.');
}
foreach (['CmsWorkerViewWidget::widget', "'Ответственный'", 'sx-lead-header__status-row', "'status' => \$status"] as $headerFragment) {
    if (strpos($header, $headerFragment) === false) {
        throw new RuntimeException('Lead owner must be rendered in the model header: '.$headerFragment);
    }
}
$responsiblePosition = strpos($header, "'Ответственный'");
$statusWidgetPosition = strpos($header, 'CmsLeadStatusWidget::widget');
if ($responsiblePosition === false || $statusWidgetPosition === false || $responsiblePosition > $statusWidgetPosition) {
    throw new RuntimeException('Lead model header must render the responsible manager before the status.');
}
foreach (['.sx-lead-header__status-row {', '.sx-lead-header__responsible {'] as $sharedHeaderStyle) {
    if (strpos($header, $sharedHeaderStyle) === false || strpos($view, $sharedHeaderStyle) !== false) {
        throw new RuntimeException('Lead header layout styles must be registered by the shared header on every action: '.$sharedHeaderStyle);
    }
}
if (strpos($header, "'isOpenNewWindow' => true") === false) {
    throw new RuntimeException('Lead company/client creation must open over the current lead card.');
}
foreach (['Взять в работу', 'Создать компанию', 'Создать клиента'] as $movedAction) {
    if (strpos($view, $movedAction) !== false) {
        throw new RuntimeException('Lead workflow action must not be duplicated inside the card: '.$movedAction);
    }
}

foreach ([
    [$companyController, "'relation'        => ['cms_company_id' => 'id']", 'company'],
    [$userController, "'relation'        => ['cms_user_id' => 'id']", 'client'],
] as [$relatedController, $relation, $owner]) {
    foreach ([
        "'leads' => [",
        "'name'     => 'Сделки'",
        "'controllerRoute' => '/cms/admin-cms-lead'",
        $relation,
        "can('cms/admin-lead')",
        "relatedIndexAction->grid['emptyState']",
    ] as $fragment) {
        if (strpos($relatedController, $fragment) === false) {
            throw new RuntimeException('The '.$owner.' card lead tab is incomplete: '.$fragment);
        }
    }
}
// Both client cards offer «Новая сделка» over their deals tab: the same card action as the block of
// actions (CrmCardTabs::createOver(), the client chosen, by the create right of deals), not the standard
// create button; the tab stands first after «Профиль» — the lowest of the shared CrmCardTabs priorities.
$leadsPriority = $tabPriorities['PRIORITY_LEADS'] ?? null;
if ($leadsPriority === null || $leadsPriority !== min($tabPriorities)) {
    throw new RuntimeException('CrmCardTabs::PRIORITY_LEADS must be the first of the card tabs.');
}
foreach (['client' => $userController, 'company' => $companyController] as $owner => $relatedController) {
    $leadsTab = preg_match("/^ {12}'leads' => \[(.*?)^ {12}\],/ms", $relatedController, $m) ? $m[1] : '';
    if (strpos($leadsTab, "'priority' => \\skeeks\\cms\\crm\\CrmCardTabs::PRIORITY_LEADS,") === false
        || strpos($leadsTab, '\skeeks\cms\crm\CrmCardTabs::createOver($action, $this->model, \'lead\', \'Новая сделка\');') === false
        || strpos($leadsTab, "'isStandartBeforeRender' => false") !== false) {
        throw new RuntimeException('The '.$owner.' card lead tab must be first after «Профиль» and offer «Новая сделка» through CrmCardTabs::createOver().');
    }
}
if (strpos(substr($companyController, (int)strpos($companyController, "'projects' => ["), 400), "'isVisible' => false") === false) {
    throw new RuntimeException('«Проекты» must not be a tab of the company card.');
}
if (strpos(file_get_contents(dirname(__DIR__).'/src/controllers/traits/LeadTriageActions.php'), '$this->presetCreateCompany(new LeadIdentityForm(') === false) {
    throw new RuntimeException('«Новая сделка» from a company card must choose that company in «Кто это».');
}
if (strpos($controller, 'if (\\Yii::$app->controller !== $this)') === false
    || strpos($controller, 'return null;', strpos($controller, 'public function getModel()')) === false
) {
    throw new RuntimeException('The lead controller must not resolve a parent pk while initializing a related grid.');
}

foreach ([
    'public function recordCreationActivity(): CmsLog',
    'public function addSystemActivity(string $message, ?int $actorId = null): CmsLog',
    "'log_type' => CmsLog::LOG_TYPE_COMMENT,",
    'if ($this->_creationActivityLog !== null)',
    'return $this->_creationActivityLog = $this->addSystemActivity(',
    'throw new \\RuntimeException(\'Не удалось сохранить запись активности сделки: \'',
] as $activityContract) {
    if (strpos($model, $activityContract) === false) {
        throw new RuntimeException('Lead system activity contract is incomplete: '.$activityContract);
    }
}
if (strpos($model, '$blameable = $log->getBehavior(BlameableBehavior::class);') === false
    || strpos($model, '$blameable->value = $actorId;') === false
    || strpos($model, '$log->created_by = $actorId;') === false
) {
    throw new RuntimeException('An explicit activity author must survive BlameableBehavior.');
}
$creationLifecyclePosition = strpos($model, 'if ($this->source_type !== self::SOURCE_FORM) {');
$creationCallPosition = strpos($model, '$this->recordCreationActivity();');
if ($creationLifecyclePosition === false || $creationCallPosition === false
    || $creationCallPosition < $creationLifecyclePosition
) {
    throw new RuntimeException('Direct manual/partner inserts must record their creation activity behind the Form2 guard.');
}
foreach ([
    "\$message = 'Отправлена форма';",
    "ArrayHelper::getValue(\$sourceData, 'form_send_id', 0)",
    '$sendId = (int)$this->source_ref;',
    'Html::encode($formName)',
    '$mainPhone = $this->mainPhone;',
    'Html::encode((string)$mainPhone->value)',
] as $formActivityContract) {
    if (strpos($model, $formActivityContract) === false) {
        throw new RuntimeException('Form2 creation entry must name the form, its submission and the stored contact: '.$formActivityContract);
    }
}
foreach ([
    "\$actor.' добавил сделку «'.\$name.'»'",
    "\$actor.' создал сделку «'.\$name.'»'",
    'CmsUser::findOne($actorId)',
    'Html::encode((string)$actor->shortDisplayName)',
    'Html::encode((string)$this->name)',
] as $actorContract) {
    if (strpos($model, $actorContract) === false) {
        throw new RuntimeException('Lead creation entry must name its author and escape dynamic text: '.$actorContract);
    }
}
if (substr_count($service, 'recordCreationActivity()') !== 1
    || strpos($service, 'if ($sourceType === CmsLead::SOURCE_FORM) {') === false
) {
    throw new RuntimeException('Only a newly ingested Form2 lead may receive the creation entry, exactly once.');
}
$serviceEmailsPosition = strpos($service, 'saveContacts($lead, CmsLeadEmail::class, $emails);');
$serviceActivityPosition = strpos($service, '$lead->recordCreationActivity();');
$serviceCommitPosition = strpos($service, '$transaction->commit();');
if ($serviceEmailsPosition === false || $serviceActivityPosition === false || $serviceCommitPosition === false
    || $serviceActivityPosition < $serviceEmailsPosition
    || $serviceActivityPosition > $serviceCommitPosition
) {
    throw new RuntimeException('The Form2 creation entry must be written after contacts and inside the ingestion transaction.');
}
$existingReturnPosition = strpos($service, 'return $existing;');
if ($existingReturnPosition === false || $existingReturnPosition > $serviceActivityPosition) {
    throw new RuntimeException('A lead reused through source_ref must not receive a second creation entry.');
}
$syncContactsPosition = strpos($service, 'public function syncContacts');
if ($syncContactsPosition === false || $serviceActivityPosition > $syncContactsPosition) {
    throw new RuntimeException('Contact synchronization of an existing lead must not create activity entries.');
}

$displayNameContracts = [
    'public function getDisplayName(): string',
    'return (string)$this->name;',
    "'value' => static fn(CmsLead \$model) => \$model->displayName",
    "'title' => \$model->displayName",
];
foreach ($displayNameContracts as $fragment) {
    if (strpos($model.$controller.$header, $fragment) === false) {
        throw new RuntimeException('Lead display name must use the persisted name: '.$fragment);
    }
}
if (strpos($model, "\$label = 'Форма на сайте «'.\$this->sourceNameAsText.'»'") !== false) {
    throw new RuntimeException('CmsLead must not rebuild a Form2 title around an editable persisted name.');
}
// П2а: the client of a deal in work is found or created in «Клиент сделки» (the duplicate check), not in bare create forms.
// The header and the block of actions take it from CrmCardActionsWidget::leadConfig(): for who works with a deal in work without a client.
if (strpos(str_replace("\r\n", "\n", $cardActions), "if (\$canWork && \$lead->status === \\skeeks\\cms\\models\\CmsLead::STATUS_IN_WORK && (!\$lead->cms_company_id || !\$lead->cms_user_id)) {\n            \$actions['identity'] = ['label' => 'Клиент сделки'") === false
    || strpos($cardActions, "'url' => \$window(['/cms/admin-cms-lead/identity', 'pk' => \$lead->id])") === false) {
    throw new RuntimeException('A deal in work must open «Клиент сделки» to find or create its client.');
}

echo "CMS lead contract: ok\n";

final class CmsLeadUtmContract
{
    public const ATTRIBUTES = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];
}
