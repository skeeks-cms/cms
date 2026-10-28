<?php

$root = dirname(__DIR__).'/src';
$query = file_get_contents($root.'/models/queries/CmsLeadQuery.php');
$controller = file_get_contents($root.'/controllers/AdminCmsLeadController.php');
$lead = file_get_contents($root.'/models/CmsLead.php');
$companyController = file_get_contents($root.'/controllers/AdminCmsLeadCompanyController.php');
$clientController = file_get_contents($root.'/controllers/AdminCmsLeadClientController.php');
$phoneController = file_get_contents($root.'/controllers/AdminCmsLeadPhoneController.php');
$emailController = file_get_contents($root.'/controllers/AdminCmsLeadEmailController.php');
$leadContact = file_get_contents($root.'/controllers/traits/LeadContactActions.php');

// Every condition on an empty executor is the first operand of an AND whose second one is the evidence (EXISTS).
$nullExecutorOnlyWithEvidence = static function (string $source): bool {
    if (!preg_match_all("~\[(?:'and'|\"and\"),\s*\[[^\]]*executor_id' => null\],\s*\['or',\s*\['exists',~", $source, $guarded)) {
        $guarded = [[]];
    }

    return substr_count($source, "executor_id' => null") === count($guarded[0]);
};

$contracts = [
    'lead query has employee CRM scope' => strpos($query, 'function forManager') !== false
        && strpos($query, 'CmsCompany::find()') !== false,
    'worker identity does not expose own partner submissions' => strpos(
        $query,
        "->andWhere(['<>', 'leadCompanyUser.cms_user_id', \$viewerId])"
    ) !== false,
    // An empty executor is allowed only together with the evidence of the client (people or contacts of a
    // company the employee works on): an unsorted deal of his client. The behaviour itself — an anonymous deal
    // stays hidden, a deal in work is not opened by contacts — is checked on data by cms-lead-company-access.php.
    'anonymous leads do not retain a common queue' => strpos($query, 'companyEvidenceCondition') !== false
        && $nullExecutorOnlyWithEvidence($query),
    'lead grid applies employee and site scope' => strpos($controller, 'dataProvider->query->forManager()->cmsSite()') !== false,
    'direct lead load applies employee scope' => strpos($controller, 'CmsLead::find()') !== false
        && strpos($controller, '->forManager()') !== false
        && strpos($controller, "throw new NotFoundHttpException('Сделка не найдена.')") !== false,
    'recipients use the same scope as the grid' => strpos($lead, 'self::find()->forManager($worker)') !== false,
    'company creation uses scoped lead' => strpos($companyController, 'CmsLead::find()') !== false
        && strpos($companyController, '->forManager()') !== false
        && strpos($companyController, '->cmsSite()') !== false,
    'client creation uses scoped lead' => strpos($clientController, 'CmsLead::find()') !== false
        && strpos($clientController, '->forManager()') !== false
        && strpos($clientController, '->cmsSite()') !== false,
    // The phone and email controllers share the trait LeadContactActions.
    'lead contact parent is restored before validation' => strpos($phoneController, 'use LeadContactActions;') !== false
        && strpos($emailController, 'use LeadContactActions;') !== false
        && strpos($leadContact, 'trustedLeadId') !== false
        && substr_count($leadContact, 'EVENT_BEFORE_VALIDATE => $lockLead') >= 2,
    'lead contact access is site scoped' => strpos($leadContact, '->forManager()') !== false
        && strpos($leadContact, '->cmsSite()') !== false,
    'lead actions restore server-owned attributes' => strpos($controller, 'private function restoreAttributes') !== false
        && substr_count($controller, '$this->restoreAttributes(') >= 2
        && strpos($controller, "'cms_site_id', 'submitted_by_id'") !== false,
    'only administrators can delete one lead at a time' => strpos($controller, 'private function canDeleteLead(): bool') !== false
        && strpos($controller, "'accessCallback' => fn() => \$this->canDeleteLead()") !== false
        && strpos($controller, 'CmsManager::PERMISSION_ROLE_ADMIN_ACCESS') !== false
        && strpos($controller, "'delete' => new UnsetArrayValue()") === false
        && strpos($controller, "'delete-multi' => new UnsetArrayValue()") !== false,
    'lead identity conversion is atomic' => strpos($companyController, 'EVENT_BEFORE_SAVE') !== false
        && strpos($companyController, 'beginTransaction()') !== false
        && strpos($companyController, '_conversionTransaction->commit()') !== false
        && strpos($clientController, 'EVENT_BEFORE_SAVE') !== false
        && strpos($clientController, 'beginTransaction()') !== false
        && strpos($clientController, '_conversionTransaction->commit()') !== false,
];

foreach ($contracts as $name => $passed) {
    if (!$passed) {
        throw new RuntimeException('Failed lead access contract: '.$name);
    }
}

// The behaviour on data (disposable SQLite), when the autoload of an installation is given or found.
$autoload = $argv[1] ?? (is_file('/app/vendor/autoload.php') ? '/app/vendor/autoload.php' : null);
if ($autoload !== null) {
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/cms-lead-company-access.php').' '.escapeshellarg($autoload).' 2>&1', $output, $status);
    if ($status !== 0) {
        throw new RuntimeException("Failed lead access contract: behaviour (cms-lead-company-access.php)\n".implode("\n", $output));
    }
}

echo "cms lead access contract: OK\n";
