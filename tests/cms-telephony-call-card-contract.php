<?php

$root = dirname(__DIR__).'/src';
$controller = file_get_contents($root.'/controllers/AdminCmsTelephonyCallController.php');
$form = file_get_contents($root.'/forms/CmsTelephonyCallLinksForm.php');
$service = file_get_contents($root.'/services/CmsTelephonyCallLinkService.php');
$leadService = file_get_contents($root.'/services/CmsLeadTelephonyService.php');
$view = file_get_contents($root.'/views/admin-cms-telephony-call/view.php');
$header = file_get_contents($root.'/views/admin-cms-telephony-call/_model_header.php');

$checks = [
    'view is the canonical call action' => strpos($controller, "modelDefaultAction = 'view'") !== false
        && strpos($controller, "'class' => BackendModelViewAction::class") !== false,
    'links are a separate standard model action' => strpos($controller, "'links' => [") !== false
        && strpos($controller, "'name' => 'Привязки'") !== false
        && strpos($controller, 'BackendModelUpdateAction::EVENT_BEFORE_SAVE') !== false,
    'all three independent relations are present in the form' => substr_count($form, 'public $cms_') === 3
        && strpos($form, 'cms_lead_id') !== false
        && strpos($form, 'cms_company_id') !== false
        && strpos($form, 'cms_user_id') !== false,
    'empty selections are allowed' => strpos($form, "'default', 'value' => null") !== false,
    'manager and site scopes are centralized' => substr_count($service, '->forManager()') >= 3
        && substr_count($service, '->cmsSite()') >= 3,
    'legacy company site scope is derived through site users' => strpos($service, 'CmsCompany2manager::find()') !== false
        && strpos($service, 'CmsCompany2user::find()') !== false,
    'link update is atomic and locks the canonical call' => strpos($service, 'beginTransaction()') !== false
        && strpos($service, 'FOR UPDATE') !== false
        && strpos($service, "array_keys(\$newTargets)") !== false,
    'activity logs point to the canonical call id' => strpos($service, 'CmsLog::LOG_TYPE_PHONE_CALL') !== false
        && strpos($service, "\$log->data = ['id' => (int)\$call->id]") !== false,
    'old relation cleanup is call-specific' => strpos($service, 'deleteCallLogs') !== false
        && strpos($service, "(int)(\$log->data['id'] ?? 0) === (int)\$call->id") !== false,
    'log projection is idempotent' => strpos($service, 'ensureCallLog') !== false
        && strpos($service, 'array_shift($matchingLogs)') !== false,
    'lead telephony reuses the common service' => strpos($leadService, 'CmsTelephonyCallLinkService') !== false
        && strpos($leadService, '->attachLead($call, $lead)') !== false,
    'card uses canonical surfaces and detail contract' => substr_count($view, 'BackendSurfaceWidget::begin') >= 3
        && strpos($view, 'sx-surface-stack') !== false
        && strpos($view, "'class' => 'sx-detail-view'") !== false,
    'card contains no deprecated or bootstrap layout markup' => strpos($view, 'sx-block') === false
        && strpos($view, 'sx-panel') === false
        && strpos($view, 'class="row') === false
        && strpos($view, 'class="col') === false,
    'recording prefers the storage file and falls back to provider URL' => strpos($view, 'cms_record_file_id') !== false
        && strpos($view, 'cmsRecordFile->src') !== false
        && strpos($view, 'record_url') !== false
        && strpos($view, "Html::tag('audio'") !== false,
    'model header uses the standard widget' => strpos($header, 'BackendModelHeader::widget') !== false,
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "cms-telephony-call-card-contract: OK\n";
