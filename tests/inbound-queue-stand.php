<?php
/**
 * «Входящие заявки» (п. 13б, crm\InboundQueue) on the stand database, rolled back:
 * no queue — the old rule; «видят все из списка» — the queue sees and is told,
 * an employee outside it does not; «по кругу» — the requests go in turns;
 * «сразу основному» — a found client with one main responsible skips
 * «Неразобранное», several main responsibles keep it there; «Взять в работу»
 * with the found company, the responsible is the actor.
 *
 * Usage: INBOUND_QUEUE_TEST=local php tests/inbound-queue-stand.php
 *        (QUEUE_IN=1663 QUEUE_OUT=110 QUEUE_SECOND=110 by default)
 */

if (getenv('INBOUND_QUEUE_TEST') !== 'local') {
    throw new RuntimeException('Set INBOUND_QUEUE_TEST=local.');
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
use skeeks\cms\crm\LeadIdentityForm;
use skeeks\cms\crm\LeadTriage;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsWebNotify;
use skeeks\cms\services\CmsLeadService;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};

$in = CmsUser::findOne((int)(getenv('QUEUE_IN') ?: 1663));
$out = CmsUser::findOne((int)(getenv('QUEUE_OUT') ?: 110));
$second = CmsUser::findOne((int)(getenv('QUEUE_SECOND') ?: 110));
$admin = CmsUser::findOne(1);
$siteId = (int)Yii::$app->skeeks->site->id;
$settings = Yii::$app->crmSettings;
$saved = [$settings->inboundWorkerIds, $settings->inboundDepartmentIds, $settings->inboundMode, $settings->inboundToMain];
$uniq = substr(md5(uniqid('', true)), 0, 6);
// A phone no record of the base has: 9 + six digits of the run.
$freshPhone = static fn() => '+7 999 '.random_int(100, 999).'-'.random_int(10, 99).'-'.random_int(10, 99);
$sees = static fn(CmsUser $user, CmsLead $lead) => CmsLead::find()->forManager($user)->andWhere([CmsLead::tableName().'.id' => (int)$lead->id])->exists();
$told = static fn(CmsUser $user, CmsLead $lead) => CmsWebNotify::find()->andWhere(['cms_user_id' => (int)$user->id,
    'model_id' => (int)$lead->id, 'name' => 'Новая сделка в Неразобранном'])->exists();
$request = static function (string $phone, string $ref = '') use ($siteId, $uniq) {
    return (new CmsLeadService())->createFromSource([
        'name' => 'Проверка: заявка '.$uniq.' '.$phone,
        'cms_site_id' => $siteId,
        'source_type' => CmsLead::SOURCE_FORM,
        'source_ref' => 'inbound-queue-test:'.$uniq.':'.$phone.$ref,
        'phones' => [$phone],
    ]);
};
$queue = static function (array $workers, string $mode, int $toMain = 0) use ($settings) {
    $settings->inboundWorkerIds = $workers;
    $settings->inboundDepartmentIds = [];
    $settings->inboundMode = $mode;
    $settings->inboundToMain = $toMain;
    InboundQueue::reset();
};

$transaction = Yii::$app->db->beginTransaction();
try {
    Yii::$app->user->setIdentity($admin);

    // 1. No queue: the old rule — an unmatched request is the administrators' sorting.
    $queue([], InboundQueue::MODE_ALL);
    $lead = $request($freshPhone());
    $check($lead->status === CmsLead::STATUS_NEW && !$lead->executor_id, 'no queue: the request stays unsorted, nobody given');
    $check(!$sees($in, $lead) && !$sees($out, $lead) && $sees($admin, $lead), 'no queue: an unmatched request — administrators only');

    // 2. «Видят все из списка»: the queue sees every unsorted request and is told; another employee is not.
    $queue([(int)$in->id], InboundQueue::MODE_ALL);
    $lead = $request($freshPhone());
    $check($sees($in, $lead) && !$sees($out, $lead), 'all of the list: the queue sees an unmatched request, an employee outside it does not');
    $check(CrmAccess::instance()->canTriage($in, $lead) && !CrmAccess::instance()->canTriage($out, $lead), 'all of the list: the queue sorts it, another employee does not');
    $check($told($in, $lead) && !$told($out, $lead), 'all of the list: «Новая сделка в Неразобранном» to the queue only');
    $notified = InboundQueue::notifyIds($lead);
    $check(in_array((int)$admin->id, $notified, true) && !in_array((int)$out->id, $notified, true), 'administrators are told by their own notification settings, not the employees of the found client');

    // «Взять в работу»: the responsible is the actor, the deal in work at the first stage.
    $result = (new LeadTriage())->accept($lead, new LeadIdentityForm(['executor_id' => (int)$in->id]), (int)$in->id);
    $lead->refresh();
    $check($lead->status === CmsLead::STATUS_IN_WORK && (int)$lead->executor_id === (int)$in->id && $lead->cms_lead_stage_id,
        '«Взять в работу»: in work, the responsible is me, the first stage');
    if (!in_array((int)$out->id, \skeeks\cms\models\CmsDepartment::leaderIdsOf((int)$in->id), true)) {
        $check(!$sees($out, $lead), 'a taken deal is not seen by an employee outside the queue');
    } else {
        $check($sees($out, $lead), 'a taken deal is seen by the leader of its responsible');
    }

    // 3. «По кругу»: two requests go to two employees in turns, unsorted, each told.
    if ((int)$second->id !== (int)$in->id) {
        $queue([(int)$in->id, (int)$second->id], InboundQueue::MODE_ROUND);
        $first = $request($freshPhone());
        $next = $request($freshPhone());
        $check($first->status === CmsLead::STATUS_NEW && $next->status === CmsLead::STATUS_NEW
            && $first->executor_id && $next->executor_id && (int)$first->executor_id !== (int)$next->executor_id,
            'by turns: two requests — two employees, both unsorted');
        $given = CmsUser::findOne((int)$first->executor_id);
        $other = CmsUser::findOne((int)$next->executor_id);
        $check($sees($given, $first) && !$sees($other, $first) && CrmAccess::instance()->canTriage($given, $first)
            && !CrmAccess::instance()->canTriage($other, $first), 'by turns: only the given employee sees and sorts it');
        $check($told($given, $first) && !$told($other, $first), 'by turns: «Новая сделка в Неразобранном» to the given one only');
        $third = $request($freshPhone());
        $check((int)$third->executor_id === (int)$first->executor_id, 'by turns: the third request starts the circle again');
    }

    // 4. «Сразу основному»: a found client with one main responsible skips «Неразобранное».
    $phone = $freshPhone();
    $company = new CmsCompany(['name' => 'Проверка: компания очереди '.$uniq]);
    $company->save(false);
    CmsCompany2manager::deleteAll(['cms_company_id' => $company->id]);
    CrmAccess::instance()->setMain('company', (int)$company->id, (int)$in->id);
    (new CmsCompanyPhone(['cms_company_id' => $company->id, 'value' => $phone]))->save(false);
    $queue([(int)$out->id], InboundQueue::MODE_ALL, 1);
    $lead = $request($phone);
    $check($lead->status === CmsLead::STATUS_IN_WORK && (int)$lead->executor_id === (int)$in->id && (int)$lead->cms_company_id === (int)$company->id,
        'to the main responsible: in work, the main one is the responsible, the company linked');
    $check(!$told($out, $lead), 'to the main responsible: the queue is not told');

    // Two found records with different main responsibles: the request stays for the queue.
    $twin = new CmsCompany(['name' => 'Проверка: компания очереди-двойник '.$uniq]);
    $twin->save(false);
    CmsCompany2manager::deleteAll(['cms_company_id' => $twin->id]);
    CrmAccess::instance()->setMain('company', (int)$twin->id, (int)$admin->id);
    (new CmsCompanyPhone(['cms_company_id' => $twin->id, 'value' => $phone, 'duplicate_reason' => 'Проверка']))->save(false);
    $lead = $request($phone, ':twin');
    $check($lead->status === CmsLead::STATUS_NEW && !$lead->executor_id, 'several main responsibles: the request stays in «Неразобранное»');
    $check($sees($out, $lead), 'several main responsibles: the queue sees it');

    echo 'OK inbound-queue-stand: '.$checks." checks\n";
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
    [$settings->inboundWorkerIds, $settings->inboundDepartmentIds, $settings->inboundMode, $settings->inboundToMain] = $saved;
    InboundQueue::reset();
}
