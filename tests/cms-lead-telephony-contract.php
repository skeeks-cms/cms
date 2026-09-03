<?php

$root = dirname(__DIR__).'/src';
$call = file_get_contents($root.'/models/CmsTelephonyCall.php');
$service = file_get_contents($root.'/services/CmsLeadTelephonyService.php');
$linkService = file_get_contents($root.'/services/CmsTelephonyCallLinkService.php');
$controller = file_get_contents($root.'/controllers/TelephonyController.php');
$webhook = file_get_contents($root.'/controllers/TelephonyWebhookController.php');
$widget = file_get_contents($root.'/telephony/widgets/TelephonyWidget.php');
$script = file_get_contents($root.'/telephony/widgets/assets/src/telephony.js');
$view = file_get_contents($root.'/views/admin-cms-lead/view.php');
$migration = file_get_contents($root.'/migrations/m260819_160000__add_cms_lead_id_to_cms_telephony_call.php');

$checks = [
    'call stores its lead relation' => strpos($call, "'cms_lead_id'") !== false && strpos($call, 'function getLead()') !== false,
    'every outgoing call is registered for its employee' => strpos($controller, "if (!empty(\$result['success']) && !empty(\$result['provider_call_id']))") !== false
        && strpos($controller, 'registerOutgoingCall(') !== false,
    'outgoing call accepts optional explicit lead context' => strpos($controller, "post('lead_id')") !== false
        && strpos($service, '?int $leadId = null') !== false,
    'explicit lead context is employee and site scoped' => strpos($service, '->forManager()') !== false
        && substr_count($service, '->cmsSite()') >= 2,
    'lead button sends its id' => strpos($view, 'data-lead-id=') !== false,
    'widget forwards lead context' => strpos($widget, 'context.lead_id') !== false,
    'client posts optional context' => strpos($script, '$.extend({ phone: phone }, context || {})') !== false,
    'confirmed webhook attaches the call' => strpos($webhook, 'attachToLead($call)') !== false,
    'phone fallback refuses ambiguous leads' => strpos($service, 'count($leads) === 1') !== false,
    'phone comparison uses shared normalization' => strpos($service, 'PhoneHelper::equalCondition') !== false,
    'telephony searches the lead phone relation' => strpos($service, 'CmsLeadPhone::tableName()') !== false
        && strpos($service, 'leadHasPhone') !== false,
    'lead call log delegates to the common atomic projection service' => strpos($service, 'CmsTelephonyCallLinkService') !== false
        && strpos($service, '->attachLead($call, $lead)') !== false,
    'lead call log is idempotent' => strpos($linkService, 'FOR UPDATE') !== false
        && strpos($linkService, "CmsLog::LOG_TYPE_PHONE_CALL") !== false,
    'provider call identity is database-unique' => strpos($migration, 'cms_telephony_call__provider_call_unique') !== false,
    'provider call duplicates are rejected before DDL' => strpos($migration, 'HAVING COUNT(*) > 1') !== false
        && strpos($migration, 'Cannot add unique provider-call index') < strpos($migration, "addColumn('cms_telephony_call'")
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "cms-lead-telephony-contract: OK\n";
