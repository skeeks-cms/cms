<?php
/**
 * Who works on a deal — the single rule CrmAccess::isLeadWorker() (owner's
 * decision 29.10.2026), on the stand database, rolled back: an administrator,
 * the responsible, his leader, an employee with "full" to the client (the main
 * one) work on it; "Свои сделки"/"edit" and outsiders do not; a leader gets
 * the level of his subordinate, never above (a deal of another employee of the
 * same company stays closed to him); closed deals are only reopened; the
 * board, "Не дозвонились" and CmsLead::canBeWorkedBy() follow the rule.
 *
 * CRM_LEAD_WORKER_STAND_TEST=local php tests/crm-lead-worker-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CRM_LEAD_WORKER_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_LEAD_WORKER_STAND_TEST=local.');
}
define('ROOT_DIR', getenv('TEST_APP_ROOT') ?: '/app');
define('YII_ENV', 'dev');
define('YII_DEBUG', true);
require ROOT_DIR.'/vendor/skeeks/cms/bootstrap.php';
$config = new Yiisoft\Config\Config(new Yiisoft\Config\ConfigPaths(ROOT_DIR, 'config'), null, [Yiisoft\Config\Modifier\RecursiveMerge::groups('web', 'web-'.ENV, 'params', 'params-web-'.ENV)], 'params-web-'.ENV);
$c = $config->get($config->has('web-'.ENV) ? 'web-'.ENV : 'web');
$c['components']['request']['scriptFile'] = ROOT_DIR.'/frontend/web/index.php';
$c['components']['request']['scriptUrl'] = '/index.php';
$c['components']['request']['hostInfo'] = 'http://localhost';
$c['components']['user']['enableSession'] = false;
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\CmsLeadBoardService;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};
$check(CrmAccess::levelsReady() && CrmAccess::mainReady(), 'the access levels and the main responsible are applied');

$db = Yii::$app->db;
$transaction = $db->beginTransaction();
try {
    $site = CmsSite::find()->default()->one();
    Yii::$app->skeeks->site = $site;

    $admin = null;
    $workers = [];
    foreach (CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->orderBy(['id' => SORT_ASC])->all() as $worker) {
        if (Yii::$app->authManager->checkAccess((int)$worker->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            $admin = $admin ?? $worker;
        } else {
            $workers[] = $worker;
        }
    }
    $check($admin !== null && count($workers) >= 5, 'the stand needs an administrator and five other employees');
    [$executor, $leader, $full, $deal, $outsider] = array_map(static fn($w) => (int)$w->id, array_slice($workers, 0, 5));

    // One department: the leader heads it, the executor works in it. Nobody else leads anybody.
    $db->createCommand()->delete('{{%cms_department2worker}}')->execute();
    $db->createCommand()->update('{{%cms_department}}', ['worker_id' => null])->execute();
    $db->createCommand()->insert('{{%cms_department}}', ['name' => 'Тест: отдел продаж', 'worker_id' => $leader, 'sort' => 100])->execute();
    $departmentId = (int)$db->getLastInsertID();
    $db->createCommand()->insert('{{%cms_department2worker}}', ['cms_department_id' => $departmentId, 'worker_id' => $executor])->execute();

    // The company: "full" main, "Свои сделки" for the executor and another employee.
    $company = CmsCompany::find()->orderBy(['id' => SORT_ASC])->one();
    $companyId = (int)$company->id;
    $db->createCommand()->delete('{{%cms_company2manager}}', ['cms_company_id' => $companyId])->execute();
    foreach ([[$full, CrmAccess::LEVEL_FULL, 1], [$executor, CrmAccess::LEVEL_DEAL, 0], [$deal, CrmAccess::LEVEL_DEAL, 0]] as [$userId, $level, $isMain]) {
        $db->createCommand()->insert('{{%cms_company2manager}}', [
            'cms_company_id' => $companyId, 'cms_user_id' => $userId, 'access_level' => $level, 'is_main' => $isMain,
        ])->execute();
    }
    $newLead = static function (int $executorId, string $status) use ($db, $companyId, $site): CmsLead {
        $db->createCommand()->insert('{{%cms_lead}}', [
            'name' => 'Тест: кто работает со сделкой', 'status' => $status, 'executor_id' => $executorId,
            'cms_company_id' => $companyId, 'cms_site_id' => (int)$site->id, 'source_type' => 'manual',
        ])->execute();

        return CmsLead::findOne((int)$db->getLastInsertID());
    };
    $lead = $newLead($executor, CmsLead::STATUS_IN_WORK);
    $otherLead = $newLead($outsider, CmsLead::STATUS_IN_WORK);
    $closed = $newLead($executor, CmsLead::STATUS_SUCCESS);

    $access = CrmAccess::instance();
    $board = CmsLeadBoardService::forLead($lead);
    $names = [(int)$admin->id => 'the administrator', $executor => 'the responsible', $leader => 'his leader',
        $full => 'the main "full" responsible of the company', $deal => '"Свои сделки" of the company', $outsider => 'an outsider'];
    $expected = [(int)$admin->id => true, $executor => true, $leader => true, $full => true, $deal => false, $outsider => false];
    foreach ($expected as $userId => $works) {
        $check($access->canWorkLead($userId, $lead) === $works, $names[$userId].($works ? ' works' : ' does not work').' on the deal');
        $check($lead->canBeWorkedBy($userId) === $works, 'CmsLead::canBeWorkedBy() is the same rule for '.$names[$userId]);
        $check($access->canAssignLead($userId, $lead) === $works, $names[$userId].($works ? ' may' : ' may not').' change the responsible');
        $check(($board->denyReason($lead, CmsLeadBoardService::COLUMN_SUCCESS, $userId) === null) === $works,
            'the stage bar and the board follow the rule for '.$names[$userId]);
        $check($access->canWorkLead($userId, $closed) === false, 'a closed deal is read-only for '.$names[$userId]);
        $check($access->canReopenLead($userId, $closed) === $works, $names[$userId].($works ? ' may' : ' may not').' reopen a closed deal');
    }
    $check($board->denyReason($lead, CmsLeadBoardService::COLUMN_SUCCESS, $deal) === 'Завершить можно только сделку в работе. '.CrmAccess::LEAD_WORK_DENIED,
        'the refusal names who works on a deal');

    // The leader takes the level of his subordinate, not above.
    $check($access->clientDataLevel($leader, 'company', $companyId) === CrmAccess::LEVEL_DEAL, 'the leader has "Свои сделки" through the executor');
    $check(!$access->canEditClientData($leader, 'company', $companyId), 'with "Свои сделки" the leader does not change the company');
    $check(!$access->canWorkLead($leader, $otherLead), 'a deal of another employee of the company stays closed to the leader');
    $check($access->canWorkLead($full, $otherLead), 'the "full" responsible works on every deal of the company');
    $check(!$access->canWorkLead($deal, $otherLead), '"Свои сделки" does not work on a deal of another employee');
    $db->createCommand()->update('{{%cms_company2manager}}', ['access_level' => CrmAccess::LEVEL_EDIT], ['cms_company_id' => $companyId, 'cms_user_id' => $executor])->execute();
    $check($access->canEditClientData($leader, 'company', $companyId), 'with "edit" of the executor the leader changes the company data');
    $check(!$access->canWorkLead($leader, $otherLead), 'with "edit" a deal of another employee still stays closed to the leader');

    echo 'OK crm-lead-worker-stand: '.$checks." checks\n";
} finally {
    $transaction->rollBack();
}
