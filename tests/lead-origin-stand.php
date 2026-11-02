<?php
/**
 * The origin of a new request (CmsLeadService::EVENT_LEAD_ORIGIN,
 * crm\LeadOriginEvent) on the stand database, rolled back, with a handler of
 * the test instead of a package:
 * - the named company — the deal at its main responsible, whatever contacts
 *   the request has; the main responsible stays;
 * - a company without a main responsible — the employee of the handler;
 * - an open deal — the request keeps its row closed as added there, the
 *   source lookup returns the deal, a repeated event asks nobody;
 * - the saved event gets the deal where the work goes on;
 * - nobody knows — the request is routed as before.
 *
 * Usage: LEAD_ORIGIN_TEST=local php tests/lead-origin-stand.php (ORIGIN_MAIN=1663 ORIGIN_OTHER=1)
 */

if (getenv('LEAD_ORIGIN_TEST') !== 'local') {
    throw new RuntimeException('Set LEAD_ORIGIN_TEST=local.');
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
use skeeks\cms\crm\InboundQueue;
use skeeks\cms\crm\LeadOriginEvent;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\CmsLeadService;
use yii\base\Event;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};

$main = CmsUser::findOne((int)(getenv('ORIGIN_MAIN') ?: 1663));
$other = CmsUser::findOne((int)(getenv('ORIGIN_OTHER') ?: 1));
$siteId = (int)Yii::$app->skeeks->site->id;
$settings = Yii::$app->crmSettings;
$saved = [$settings->inboundWorkerIds, $settings->inboundDepartmentIds, $settings->inboundMode, $settings->inboundToMain];
$uniq = substr(md5(uniqid('', true)), 0, 6);
$freshPhone = static fn() => '+7 999 '.random_int(100, 999).'-'.random_int(10, 99).'-'.random_int(10, 99);
$request = static fn(string $ref) => (new CmsLeadService())->createFromSource([
    'name' => 'Проверка: заявка '.$uniq.' '.$ref, 'cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM,
    'source_ref' => 'lead-origin-test:'.$uniq.':'.$ref, 'phones' => [$freshPhone()], 'status' => CmsLead::STATUS_NEW,
]);
$company = static function (?CmsUser $mainUser) use ($uniq) {
    $company = new CmsCompany(['name' => 'Проверка: компания источника '.$uniq]);
    $company->save(false);
    CmsCompany2manager::deleteAll(['cms_company_id' => (int)$company->id]);
    if ($mainUser) {
        CrmAccess::instance()->setMain('company', (int)$company->id, (int)$mainUser->id);
    }
    return $company;
};

// The answer of the handler for the next request; null — it knows nothing.
$answer = null;
$asked = 0;
$savedTargets = [];
$onOrigin = static function (LeadOriginEvent $event) use (&$answer, &$asked) {
    $asked++;
    foreach ((array)$answer as $name => $value) {
        $event->$name = $value;
    }
    $event->reason = 'Проверка: источник известен';
    $event->executorRole = 'сотруднику проверки';
};
$onSaved = static function (LeadOriginEvent $event) use (&$savedTargets) {
    $savedTargets[] = [(int)$event->lead->id, (int)$event->target->id];
};
Event::on(CmsLeadService::class, CmsLeadService::EVENT_LEAD_ORIGIN, $onOrigin);
Event::on(CmsLeadService::class, CmsLeadService::EVENT_LEAD_ORIGIN_SAVED, $onSaved);

$transaction = Yii::$app->db->beginTransaction();
try {
    $settings->inboundWorkerIds = [(int)$other->id];
    $settings->inboundDepartmentIds = [];
    $settings->inboundMode = InboundQueue::MODE_ALL;
    $settings->inboundToMain = 0;
    InboundQueue::reset();

    // 1. Nobody knows: as before.
    $lead = $request('none');
    $check($lead->status === CmsLead::STATUS_NEW && !$lead->executor_id && !$lead->cms_company_id && $asked === 1 && !$savedTargets,
        'nobody knows: «Неразобранное», no saved event');

    // 2. The named company: its main responsible, the company linked, the main stays.
    $client = $company($main);
    $answer = ['companyId' => (int)$client->id, 'executorId' => (int)$other->id];
    $lead = $request('company');
    $check((int)$lead->cms_company_id === (int)$client->id && (int)$lead->executor_id === (int)$main->id && $lead->status === CmsLead::STATUS_IN_WORK,
        'the named company: in work at its main responsible');
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$client->id) === (int)$main->id, 'the main responsible stays');
    $check(end($savedTargets) === [(int)$lead->id, (int)$lead->id], 'the saved event: the request is the deal');

    // 3. A company without a main responsible: the employee of the handler.
    $orphan = $company(null);
    $answer = ['companyId' => (int)$orphan->id, 'executorId' => (int)$other->id];
    $lead = $request('orphan');
    $check((int)$lead->executor_id === (int)$other->id && (int)$lead->cms_company_id === (int)$orphan->id, 'no main responsible: the employee of the handler');
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$orphan->id) === null, 'no main responsible is made');

    // 4. An open deal: the request is added there.
    $deal = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Проверка: открытая сделка '.$uniq, 'executor_id' => (int)$main->id,
        'cms_company_id' => (int)$client->id, 'status' => CmsLead::STATUS_IN_WORK, 'source_type' => CmsLead::SOURCE_MANUAL]);
    $deal->save(false);
    $answer = ['continueLead' => $deal];
    $result = $request('deal');
    $row = CmsLead::find()->fromSource(CmsLead::SOURCE_FORM, 'lead-origin-test:'.$uniq.':deal', $siteId)->one();
    $check((int)$result->id === (int)$deal->id, 'an open deal: the request goes on there');
    $check($row && $row->status === CmsLead::STATUS_REJECTED && $row->triage_result === CmsLead::TRIAGE_MERGED && (int)$row->merged_into_id === (int)$deal->id
        && (int)$row->executor_id === (int)$main->id, 'the request keeps its row, closed as added');
    $check(end($savedTargets) === [(int)$row->id, (int)$deal->id], 'the saved event: the target is the open deal');
    $before = $asked;
    $check((int)$request('deal')->id === (int)$deal->id && $asked === $before, 'a repeated event returns the deal and asks nobody');

    // 5. A closed deal is never continued: the request is routed by the company.
    $deal->updateAttributes(['status' => CmsLead::STATUS_SUCCESS]);
    $answer = ['continueLead' => $deal, 'companyId' => (int)$client->id];
    $lead = $request('closed');
    $check((int)$lead->id !== (int)$deal->id && (int)$lead->executor_id === (int)$main->id, 'a closed deal: a new deal at the main responsible');

    echo 'OK lead-origin-stand: '.$checks." checks\n";
} finally {
    Event::off(CmsLeadService::class, CmsLeadService::EVENT_LEAD_ORIGIN, $onOrigin);
    Event::off(CmsLeadService::class, CmsLeadService::EVENT_LEAD_ORIGIN_SAVED, $onSaved);
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
    [$settings->inboundWorkerIds, $settings->inboundDepartmentIds, $settings->inboundMode, $settings->inboundToMain] = $saved;
    InboundQueue::reset();
}
