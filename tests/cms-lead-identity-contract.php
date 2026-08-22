<?php

$root = dirname(__DIR__).'/src';
$controller = file_get_contents($root.'/controllers/AdminCmsLeadController.php');
$service = file_get_contents($root.'/services/CmsLeadIdentityService.php');
$view = file_get_contents($root.'/views/admin-cms-lead/view.php');
$partial = file_get_contents($root.'/views/admin-cms-lead/_matches.php');
$asset = file_get_contents($root.'/assets/admin/LeadMatchesAsset.php');
$script = file_get_contents($root.'/assets/admin/src/lead-matches.js');

$checks = [
    'card defers matching to a separate request' => strpos($view, 'data-sx-lead-matches') !== false
        && strpos($view, "'/cms/admin-cms-lead/matches'") !== false,
    'matching endpoint accepts ajax reads only' => strpos($controller, 'public function matches()') !== false
        && strpos($controller, 'request->isAjax') !== false,
    'link endpoint requires explicit post' => strpos($controller, 'public function linkIdentity()') !== false
        && strpos($controller, 'request->isPost') !== false,
    'linking is limited to a workable lead' => strpos($controller, "'accessCallback' => fn() => \$this->canLinkIdentity()") !== false
        && strpos($service, 'CmsLead::STATUS_IN_WORK') !== false,
    'link controls use the same in-work rule as the service' => strpos($controller, "'canLink' => \$this->canLinkIdentity()") !== false
        && strpos($controller, '$this->model->status === CmsLead::STATUS_IN_WORK') !== false
        && strpos($partial, 'if ($canLink && !$model->cms_company_id)') !== false
        && strpos($partial, 'if (!$canLink)') !== false,
    'expected link failures return to the card' => substr_count($controller, "session->setFlash('error'") >= 2
        && strpos($controller, "return \$this->redirect(['view', 'pk' => \$this->model->id]);") !== false,
    'phone matching uses every lead phone' => strpos($service, 'foreach ($lead->phones as $phone)') !== false
        && strpos($service, '->phone($phone->value)') !== false,
    'email matching uses every lead email' => strpos($service, 'foreach ($lead->emails as $email)') !== false
        && strpos($service, '->email($email->value)') !== false
        && strpos($service, 'CmsCompanyEmail::find()') !== false,
    'names are not identity evidence' => strpos($service, 'collectClientNameMatches') === false
        && strpos($service, 'collectCompanyNameMatches') === false
        && strpos($service, 'Совпало имя') === false
        && strpos($service, 'Совпало название') === false,
    'linked companies are loaded from the factual relation' => strpos($service, 'CmsCompany2user::find()') !== false
        && strpos($service, "'cms_user_id' => \$clientIds") !== false,
    'combined linking verifies the client-company relation' => strpos($service, "'cms_company_id' => \$company->id") !== false
        && strpos($service, "'cms_user_id' => \$client->id") !== false
        && strpos($service, '->exists()') !== false,
    'existing links cannot be overwritten' => strpos($service, 'К лиду уже привязана другая компания.') !== false
        && strpos($service, 'К лиду уже привязан другой клиент.') !== false,
    'linking produces an auditable lead activity' => strpos($service, "'action' => 'lead_identity_link'") !== false,
    'candidate evidence is visible before linking' => strpos($partial, "implode(' · ', \$match['reasons'])") !== false,
    'client and related company can be linked together' => strpos($partial, "'Привязать оба'") !== false,
    'ajax surface has a stable accessible heading id' => strpos($partial, "'id' => 'cms-lead-matches-surface'") !== false,
    'empty results remove the optional surface' => strpos($script, 'element.remove()') !== false,
    'ajax failure offers recovery' => strpos($script, 'data-sx-lead-matches-retry') !== false,
    'matching javascript is shipped through an asset' => strpos($asset, "'lead-matches.js'") !== false,
];

if (strpos($service, 'CmsCompany::find()->forManager()->cmsSite()') !== false
    || substr_count($service, 'CmsCompany::find()->forManager()') < 5
) {
    throw new RuntimeException('Lead company matching must use manager scope without a nonexistent company site column.');
}

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "cms-lead-identity-contract: OK\n";
