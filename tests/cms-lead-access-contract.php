<?php

$root = dirname(__DIR__).'/src';
$query = file_get_contents($root.'/models/queries/CmsLeadQuery.php');
$controller = file_get_contents($root.'/controllers/AdminCmsLeadController.php');
$lead = file_get_contents($root.'/models/CmsLead.php');
$companyController = file_get_contents($root.'/controllers/AdminCmsLeadCompanyController.php');
$clientController = file_get_contents($root.'/controllers/AdminCmsLeadClientController.php');
$phoneController = file_get_contents($root.'/controllers/AdminCmsLeadPhoneController.php');
$emailController = file_get_contents($root.'/controllers/AdminCmsLeadEmailController.php');

$contracts = [
    'lead query has employee CRM scope' => strpos($query, 'function forManager') !== false
        && strpos($query, 'CmsUser::find()') !== false
        && strpos($query, 'CmsCompany::find()') !== false,
    'worker identity does not expose own partner submissions' => strpos(
        $query,
        "->andWhere(['<>', CmsUser::tableName().'.id', (int)\$user->id])"
    ) !== false,
    'unassigned anonymous leads retain a common queue' => strpos($query, ".executor_id' => null") !== false
        && strpos($query, ".submitted_by_id' => null") !== false
        && strpos($query, ".partner_id' => null") !== false,
    'lead grid applies employee and site scope' => strpos($controller, 'dataProvider->query->forManager()->cmsSite()') !== false,
    'direct lead load applies employee scope' => strpos($controller, 'CmsLead::find()') !== false
        && strpos($controller, '->forManager()') !== false
        && strpos($controller, "throw new NotFoundHttpException('Лид не найден.')") !== false,
    'partner leads do not fall back to every worker' => strpos($lead, 'if (!$userIds)') === false
        && strpos($lead, 'CmsUser2manager::find()') !== false,
    'company creation uses scoped lead' => strpos($companyController, 'CmsLead::find()') !== false
        && strpos($companyController, '->forManager()') !== false
        && strpos($companyController, '->cmsSite()') !== false,
    'client creation uses scoped lead' => strpos($clientController, 'CmsLead::find()') !== false
        && strpos($clientController, '->forManager()') !== false
        && strpos($clientController, '->cmsSite()') !== false,
    'lead contact parent is restored before validation' => strpos($phoneController, 'trustedLeadId') !== false
        && strpos($phoneController, 'EVENT_BEFORE_VALIDATE') !== false
        && strpos($emailController, 'trustedLeadId') !== false
        && strpos($emailController, 'EVENT_BEFORE_VALIDATE') !== false,
    'lead contact access is site scoped' => strpos($phoneController, '->cmsSite()') !== false
        && strpos($emailController, '->cmsSite()') !== false,
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

echo "cms lead access contract: OK\n";
