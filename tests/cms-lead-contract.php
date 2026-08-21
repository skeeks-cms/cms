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

$requiredModelFragments = [
    "[['name', 'source_type', 'status'], 'required']",
    'public function getPhones()',
    'public function getEmails()',
    'public function getMainPhone()',
    'public function getMainEmail()',
    "self::SOURCE_PARTNER => 'Партнёрская программа'",
    'public function allowedNextStatuses(): array',
    'self::STATUS_NEW => [self::STATUS_NEW, self::STATUS_IN_WORK]',
    'self::STATUS_IN_WORK => [self::STATUS_IN_WORK, self::STATUS_SUCCESS, self::STATUS_REJECTED]',
    'notifyAvailableManagers()',
    'notifyPartnerAboutComment(?int $logId = null)',
    'getPartnerViewUrl(?int $logId = null)',
    "['sx-log-id' => \$logId]",
    "'#sx-log-'.\$logId",
    'notifyManagersAboutPartnerComment()',
    "'Вам назначен новый лид'",
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

foreach (["'priority' => 10", "'priority' => 20", "'priority' => 30", "'priority' => 40"] as $priority) {
    if (strpos($controller, $priority) === false) {
        throw new RuntimeException('Missing lead-card action priority: '.$priority);
    }
}
if (strpos($controller, "'visibleFilters' => ['q', 'status', 'source_type']") === false) {
    if (strpos($controller, "array_merge(['q', 'status', 'source_type'], \$utmAttributes)") === false) {
        throw new RuntimeException('Lead filters must include source and canonical UTM attribution fields.');
    }
}
if (strpos($controller, "'update' => new UnsetArrayValue()") === false) {
    throw new RuntimeException('The inherited update action must not duplicate the dedicated lead edit action.');
}
foreach (["'label' => 'Результат обработки'", "'label' => 'Причина отклонения'"] as $label) {
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
if (strpos($controller, '$model->executor_id') === false
    || strpos($controller, '? CmsLead::STATUS_IN_WORK') === false
    || strpos($controller, "\$fields['executor_id'] = \$this->managerField()") === false
    || strpos($controller, '$model = $action ? $action->model : null') === false
    || strpos($controller, '$model && $model->isNewRecord') === false
    || strpos($controller, '$model->executor_id = (int)\\Yii::$app->user->id') === false
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
$lead = strpos($menu, "'label'    => \\Yii::t('skeeks/cms', 'Лиды')");
if ($company === false || $client === false || $lead === false) {
    throw new RuntimeException('CRM menu entries are incomplete.');
}
if (!($company < $client && $client < $lead)
    || !preg_match("/'Компании'\)[\\s\\S]*?'priority'\s*=>\s*100/", $menu)
    || !preg_match("/'Клиенты'\)[\\s\\S]*?'priority'\s*=>\s*110/", $menu)
    || !preg_match("/'Лиды'\)[\\s\\S]*?'priority'\s*=>\s*120/", $menu)
) {
    throw new RuntimeException('CRM menu order must be Companies, Clients, Leads.');
}
if (!preg_match("/'name'\s*=>\s*'cms\/admin-lead'[\s\S]*?'description'\s*=>\s*\['skeeks\/cms',\s*'Лиды'\]/", $permissions)) {
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
foreach (['sx-detail-layout__aside', 'sx-detail-layout__main', 'Добавить комментарий', 'CmsLog::LOG_TYPE_PHONE_CALL', 'CmsLog::LOG_TYPE_COMMENT'] as $activityFragment) {
    if (strpos($view, $activityFragment) === false) {
        throw new RuntimeException('Lead card must use the company-style split activity layout: '.$activityFragment);
    }
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
foreach (['Добавить телефон', 'Добавить email', 'sx-send-sms-trigger', 'sx-telephony-btn', '/cms/admin-cms-lead-phone', '/cms/admin-cms-lead-email'] as $fragment) {
    if (strpos($view, $fragment) === false) {
        throw new RuntimeException('Lead contact surface is incomplete: '.$fragment);
    }
}
if (strpos($controller, "@skeeks/cms/views/admin-cms-lead/_model_header") === false) {
    throw new RuntimeException('Lead workflow actions must be available from the shared model header on every lead action.');
}
foreach (['Взять в работу', 'Создать компанию', 'Создать клиента', 'BackendModelHeader::widget', 'CmsLeadStatusWidget::widget'] as $headerFragment) {
    if (strpos($header, $headerFragment) === false) {
        throw new RuntimeException('Lead model header is incomplete: '.$headerFragment);
    }
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
