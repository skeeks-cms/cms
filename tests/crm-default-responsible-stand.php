<?php
/**
 * «Ответственный по умолчанию» (crmSettings->defaultResponsibleId): no fixed employee id in the
 * code — CrmAccess::defaultResponsible() takes the setting when the employee is active, else the
 * first active administrator, else nobody; every fallback uses it (ensureMain(), a company created
 * without an employee, a client with a history, the console plans). Rolled back on the stand database.
 *
 * CRM_DEFAULT_RESPONSIBLE_TEST=local php tests/crm-default-responsible-stand.php
 *   TEST_APP_ROOT (/app); TEST_CHOSEN_ID — an active employee who is not an administrator (237);
 *   TEST_LONELY_ID — an employee in no department (217)
 */
if (getenv('CRM_DEFAULT_RESPONSIBLE_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_DEFAULT_RESPONSIBLE_TEST=local.');
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
use skeeks\cms\crm\CrmProtection;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsSite;
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
$settings = Yii::$app->crmSettings;
$saved = $settings->defaultResponsibleId;
try {
    Yii::$app->skeeks->site = CmsSite::find()->default()->one();
    $access = CrmAccess::instance();
    $firstAdmin = (int)($access->adminIds()[0] ?? 0);
    $chosen = CmsUser::findOne((int)(getenv('TEST_CHOSEN_ID') ?: 237));
    $lonely = CmsUser::findOne((int)(getenv('TEST_LONELY_ID') ?: 217));
    $expect($firstAdmin > 0 && $chosen && !$access->isAdmin($chosen) && $lonely && !CmsDepartment::leaderIdsOf((int)$lonely->id),
        'на стенде есть администратор, сотрудник без прав администратора и сотрудник вне отделов');

    // --- the choice ---
    $settings->defaultResponsibleId = (int)$chosen->id;
    $expect($access->defaultResponsible() === [(int)$chosen->id, CrmAccess::DEFAULT_FROM_SETTINGS], 'выбран в настройках и активен: он, «из настроек CRM»');
    $settings->defaultResponsibleId = null;
    $expect($access->defaultResponsible() === [$firstAdmin, CrmAccess::DEFAULT_FIRST_ADMIN], 'не выбран: первый активный администратор');
    $settings->defaultResponsibleId = (int)$chosen->id;
    $db->createCommand()->update('{{%cms_user}}', ['is_active' => 0], ['id' => (int)$chosen->id])->execute();
    $expect($access->defaultResponsible() === [$firstAdmin, CrmAccess::DEFAULT_FIRST_ADMIN], 'выбран неактивный сотрудник: первый активный администратор');
    $db->createCommand()->update('{{%cms_user}}', ['is_active' => 1], ['id' => (int)$chosen->id])->execute();
    $client = (int)CmsUser::find()->andWhere(['is_worker' => 0])->select('id')->orderBy(['id' => SORT_DESC])->scalar();
    $settings->defaultResponsibleId = $client;
    $expect($access->defaultResponsible() === [$firstAdmin, CrmAccess::DEFAULT_FIRST_ADMIN], 'выбран не сотрудник: первый активный администратор');
    $settings->defaultResponsibleId = (int)$chosen->id;

    // --- every fallback takes it ---
    // A client left without the main one and without a head of a department (ensureMain()).
    $db->createCommand()->insert('{{%cms_company}}', ['name' => 'Проверка: ответственный по умолчанию', 'created_at' => time()])->execute();
    $companyId = (int)$db->getLastInsertID();
    $db->createCommand()->insert('{{%cms_company2manager}}', ['cms_company_id' => $companyId, 'cms_user_id' => (int)$lonely->id, 'access_level' => 'deal', 'is_main' => 0])->execute();
    $access->ensureMain('company', $companyId);
    $expect($access->mainResponsibleId('company', $companyId) === (int)$chosen->id, 'клиент без основного и без руководителя отдела: основной — ответственный по умолчанию');
    $comment = (string)(new \yii\db\Query())->select('comment')->from('cms_crm_access_log')
        ->where(['record_code' => 'company', 'record_id' => $companyId])->orderBy(['id' => SORT_DESC])->scalar();
    $expect(mb_strpos($comment, 'ответственный по умолчанию (из настроек CRM)') !== false, 'журнал «Доступы»: основание «ответственный по умолчанию (из настроек CRM)»');

    // A company created without an employee (the console: no identity, no author).
    Yii::$app->user->setIdentity(null);
    $company = new CmsCompany(['name' => 'Проверка: компания из системы']);
    $company->save(false);
    $expect($access->mainResponsibleId('company', (int)$company->id) === (int)$chosen->id, 'компания, созданная без сотрудника: основной — ответственный по умолчанию');

    // A client with no history: «Работать с этим клиентом» and the console plans.
    $db->createCommand()->insert('{{%cms_company}}', ['name' => 'Проверка: без истории', 'created_at' => time()])->execute();
    $bare = (int)$db->getLastInsertID();
    $expect((new CrmProtection())->assignHistoryMain('company', $bare) === (int)$chosen->id, 'клиент без истории: основной — ответственный по умолчанию');
    $expect((new CrmProtection())->fallback() === [(int)$chosen->id, 'ответственный по умолчанию ('.CrmAccess::DEFAULT_FROM_SETTINGS.')'],
        'консольные планы без --user: ответственный по умолчанию и почему');
    $expect((new CrmProtection())->fallback((int)$lonely->id) === [(int)$lonely->id, 'выбран в команде'], 'консольные планы с --user: выбранный сотрудник');

    // No fixed id in the code: the constant stays only as a deprecated declaration.
    $uses = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__).'/src')) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && strpos(file_get_contents($file->getPathname()), 'HISTORY_FALLBACK_USER_ID') !== false) {
            $uses[] = basename($file->getPathname());
        }
    }
    $expect($uses === ['CrmProtection.php'] && substr_count(file_get_contents(dirname(__DIR__).'/src/crm/CrmProtection.php'), 'HISTORY_FALLBACK_USER_ID') === 1,
        'HISTORY_FALLBACK_USER_ID только объявлена (@deprecated), в логике не используется');
} catch (\Throwable $e) {
    $expect(false, get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')');
} finally {
    $settings->defaultResponsibleId = $saved;
    $transaction->rollBack();
}
echo $fail ? "FAIL crm-default-responsible-stand: $fail of $checks\n" : "OK crm-default-responsible-stand: $checks checks\n";
exit($fail ? 1 : 0);
