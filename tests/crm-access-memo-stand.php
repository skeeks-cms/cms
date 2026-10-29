<?php
/**
 * The answers of rights kept for one web request (audit F10): CrmAccess::memo() and
 * CmsDepartment::tree() never outlive a change of rights inside the request — a saved or
 * deleted link, a level set by CrmAccess, a department or its employee, another identity.
 * Rolled back on the stand database; the memo is switched on for the console only here.
 *
 * CRM_ACCESS_MEMO_TEST=local php tests/crm-access-memo-stand.php
 *   TEST_APP_ROOT (/app); TEST_ADMIN_ID (1); TEST_NEW_USER_ID — an employee without links (237)
 */
if (getenv('CRM_ACCESS_MEMO_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_ACCESS_MEMO_TEST=local.');
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
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsDepartment2worker;
use skeeks\cms\models\CmsUser;

$fail = 0;
$checks = 0;
$expect = static function (bool $ok, string $what) use (&$fail, &$checks) {
    $checks++;
    $fail += $ok ? 0 : 1;
    echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n";
};

$db = Yii::$app->db;
$transaction = $db->beginTransaction();
CrmAccess::$keepAnswersInConsole = true;
try {
    $access = CrmAccess::instance();
    $access->resetMemo();
    $admin = CmsUser::findOne((int)(getenv('TEST_ADMIN_ID') ?: 1));
    $user = CmsUser::findOne((int)(getenv('TEST_NEW_USER_ID') ?: 237));
    Yii::$app->user->setIdentity($user);
    $db->createCommand()->insert('{{%cms_company}}', ['name' => 'Проверка: кеш прав', 'created_at' => time()])->execute();
    $companyId = (int)$db->getLastInsertID();
    $db->createCommand()->insert('{{%cms_company2manager}}', ['cms_company_id' => $companyId, 'cms_user_id' => (int)$admin->id, 'access_level' => 'full', 'is_main' => 1])->execute();
    $access->resetMemo();

    $expect(!$access->canSeeCompany($user, $companyId) && $access->linkLevel($user, 'company', $companyId) === null, 'нет связи: компания не видна');
    $link = new CmsCompany2manager(['cms_company_id' => $companyId, 'cms_user_id' => (int)$user->id]);
    $link->access_level = CrmAccess::LEVEL_DEAL;
    $link->save(false);
    $expect($access->canSeeCompany($user, $companyId) && $access->linkLevel($user, 'company', $companyId) === CrmAccess::LEVEL_DEAL,
        'связь сохранена моделью: ответ сброшен, компания видна, уровень «Свои сделки»');
    $access->setLevel('company', $companyId, (int)$user->id, CrmAccess::LEVEL_EDIT);
    $expect($access->linkLevel($user, 'company', $companyId) === CrmAccess::LEVEL_EDIT, 'уровень изменён setLevel() (updateAll): новый уровень');
    $access->removeLink('company', $companyId, (int)$user->id);
    $expect(!$access->canSeeCompany($user, $companyId) && $access->linkLevel($user, 'company', $companyId) === null, 'связь убрана: компания снова не видна');

    // A raw write bypasses the models: the answer of this request stays until the identity changes or a reset.
    $db->createCommand()->insert('{{%cms_company2manager}}', ['cms_company_id' => $companyId, 'cms_user_id' => (int)$user->id, 'access_level' => 'deal', 'is_main' => 0])->execute();
    $expect(!$access->canSeeCompany($user, $companyId), 'запись мимо моделей: ответ запроса прежний (кеш действительно работает)');
    Yii::$app->user->setIdentity($admin);
    $expect($access->canSeeCompany($user, $companyId), 'другой пользователь вошёл: ответы сброшены');
    Yii::$app->user->setIdentity($user);

    // The department tree: a saved employee of a department is seen at once.
    // A new department headed by the employee without subordinates; its member is another employee.
    $memberUser = CmsUser::find()->isWorker()->andWhere(['not in', CmsUser::tableName().'.id', [(int)$admin->id, (int)$user->id]])->orderBy(['id' => SORT_DESC])->one();
    $db->createCommand()->insert('{{%cms_department}}', ['name' => 'Проверка: отдел', 'worker_id' => (int)$user->id, 'sort' => 100])->execute();
    $departmentId = (int)$db->getLastInsertID();
    CmsDepartment::resetTree();
    $before = CmsDepartment::subordinateIds((int)$user->id);
    $expect(!in_array((int)$memberUser->id, $before, true), 'сотрудник ещё не в отделе руководителя');
    $member = new CmsDepartment2worker(['cms_department_id' => $departmentId, 'worker_id' => (int)$memberUser->id]);
    $member->save(false);
    $expect(in_array((int)$memberUser->id, CmsDepartment::subordinateIds((int)$user->id), true)
        && in_array((int)$user->id, CmsDepartment::leaderIdsOf((int)$memberUser->id), true), 'сотрудник сохранён в отдел: дерево перечитано');
    $member->delete();
    $expect(!in_array((int)$memberUser->id, CmsDepartment::subordinateIds((int)$user->id), true), 'сотрудник убран из отдела: дерево перечитано');
} catch (\Throwable $e) {
    $expect(false, get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')');
} finally {
    CrmAccess::$keepAnswersInConsole = false;
    CrmAccess::instance()->resetMemo();
    $transaction->rollBack();
}
echo $fail ? "FAIL crm-access-memo-stand: $fail of $checks\n" : "OK crm-access-memo-stand: $checks checks\n";
exit($fail ? 1 : 0);
