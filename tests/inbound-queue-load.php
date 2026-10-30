<?php
/**
 * «По кругу» under load (п. 13б, release audit F3): many real processes create
 * requests at the same moment through the whole CmsLeadService::createFromSource()
 * — the turn of the queue, the deal, its contacts, the notifications — on a new
 * queue (no row yet) and on an existing one. One participant rolls its request
 * back, two send one event twice (one source_ref). Expected: no process fails,
 * every request is saved exactly once, the rolled back one is not, the repeated
 * event is one deal, the turn alternates over the saved deals, one notification
 * per deal and person. Notifications are stored, never delivered (the delivery
 * handlers are detached in every process: a fake transport).
 *
 * The site's own turn row (queue «default») is saved before and restored after;
 * the deals of this run («Проверка: нагрузка очереди <run>-…», QUEUE_RUN) with
 * their feed, access journal, notifications and deliveries are removed by exact
 * pairs and checked to be gone (a neighbour run is not touched).
 *
 * Usage: INBOUND_QUEUE_TEST=local php tests/inbound-queue-load.php
 *        (QUEUE_MEMBERS=1663,110, QUEUE_PROCESSES=14 by default)
 */

if (getenv('INBOUND_QUEUE_TEST') !== 'local') {
    throw new RuntimeException('Set INBOUND_QUEUE_TEST=local.');
}
$role = $argv[1] ?? 'main';
$members = getenv('QUEUE_MEMBERS') ?: '1663,110';
$processes = max(12, (int)(getenv('QUEUE_PROCESSES') ?: 14));
$run = getenv('QUEUE_RUN') ?: substr(md5(uniqid('', true)), 0, 8);
const LOAD_PREFIX = 'Проверка: нагрузка очереди ';

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

use skeeks\cms\crm\InboundQueue;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsWebNotify;
use skeeks\cms\notify\Notifications;
use skeeks\cms\services\CmsLeadService;

$siteId = (int)Yii::$app->skeeks->site->id;
$rotation = '{{%cms_crm_inbound_rotation}}';
$db = Yii::$app->db;

if ($role !== 'main') {
    // A child: the queue «по кругу» in memory, no delivery of notifications (the fake transport).
    $settings = Yii::$app->crmSettings;
    $settings->inboundWorkerIds = array_map('intval', explode(',', $members));
    $settings->inboundDepartmentIds = [];
    $settings->inboundMode = InboundQueue::MODE_ROUND;
    $settings->inboundToMain = 0;
    InboundQueue::reset();
    Yii::$app->notifications->off(Notifications::EVENT_CREATED);
    Yii::$app->notifications->channels = [];
    Yii::$app->notifications->resetChannels();
    [$kind, $ref] = explode('|', $argv[2], 2);
    // Start together: every child waits for the same moment.
    $at = (float)$argv[3];
    while (microtime(true) < $at) {
        usleep(2000);
    }
    $attributes = [
        'name' => LOAD_PREFIX.$ref, 'cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM,
        'source_ref' => 'inbound-queue-load:'.$ref, 'phones' => ['+7 999 '.random_int(100, 999).'-'.random_int(10, 99).'-'.random_int(10, 99)],
    ];
    try {
        if ($kind === 'rollback') {
            $outer = $db->beginTransaction();
            $lead = (new CmsLeadService())->createFromSource($attributes);
            $outer->rollBack();
            echo "ROLLBACK\n";
        } else {
            $lead = (new CmsLeadService())->createFromSource($attributes);
            echo 'LEAD '.(int)$lead->id.' '.(int)$lead->executor_id."\n";
        }
        exit(0);
    } catch (\Throwable $e) {
        fwrite(STDERR, get_class($e).': '.$e->getMessage()."\n");
        exit(2);
    }
}

/**
 * Removes the deals of THIS run only (source_ref of $run; a neighbour run keeps its own) and everything they
 * left: their feed (cms_log), the journal of the access, the notifications and their deliveries, by exact pairs.
 * The notifications and deliveries are collected before the deals go, the journals cleaned after delete() (it
 * writes its own entries). Returns what is left of them (empty — clean).
 */
$cleanup = static function () use ($siteId, $run, $db): array {
    $leads = CmsLead::find()->andWhere(['cms_site_id' => $siteId])->andWhere(['like', 'source_ref', 'inbound-queue-load:'.$run.'-%', false])->all();
    $ids = array_map(static fn(CmsLead $lead) => (int)$lead->id, $leads);
    if (!$ids) {
        return [];
    }
    $codes = array_values(array_unique([CmsLead::class, (new CmsLead())->skeeksModelCode]));
    $pairs = ['and', ['model_code' => $codes], ['model_id' => $ids]];
    $deliveriesReady = $db->getTableSchema('{{%cms_comm_notify_delivery}}', true) !== null;
    $accessReady = $db->getTableSchema('{{%cms_crm_access_log}}', true) !== null;
    $notifyIds = static fn() => array_map('intval', (new \yii\db\Query())->select('id')->from('{{%cms_web_notify}}')->andWhere($pairs)->column($db));
    $deliveryIds = static fn(array $notify) => $deliveriesReady && $notify
        ? array_map('intval', (new \yii\db\Query())->select('id')->from('{{%cms_comm_notify_delivery}}')->andWhere(['cms_web_notify_id' => $notify])->column($db)) : [];
    $notifies = $notifyIds();
    $sends = $deliveryIds($notifies);
    foreach ($leads as $lead) {
        $lead->delete();
    }
    $db->createCommand()->delete('{{%cms_log}}', $pairs)->execute();
    if ($accessReady) {
        $db->createCommand()->delete('{{%cms_crm_access_log}}', ['cms_lead_id' => $ids])->execute();
    }
    $notifies = array_values(array_unique(array_merge($notifies, $notifyIds())));
    $sends = array_values(array_unique(array_merge($sends, $deliveryIds($notifies))));
    if ($sends) {
        $db->createCommand()->delete('{{%cms_comm_notify_delivery}}', ['id' => $sends])->execute();
    }
    if ($notifies) {
        $db->createCommand()->delete('{{%cms_web_notify}}', ['id' => $notifies])->execute();
    }
    $count = static fn(string $table, array $where) => (int)(new \yii\db\Query())->from($table)->andWhere($where)->count('*', $db);

    return array_filter([
        'deals' => $count('{{%cms_lead}}', ['id' => $ids]),
        'contacts' => $count('{{%cms_lead_phone}}', ['cms_lead_id' => $ids]),
        'cms_log' => $count('{{%cms_log}}', $pairs),
        'cms_crm_access_log' => $accessReady ? $count('{{%cms_crm_access_log}}', ['cms_lead_id' => $ids]) : 0,
        'cms_web_notify' => $count('{{%cms_web_notify}}', $pairs),
        'cms_comm_notify_delivery' => $sends ? $count('{{%cms_comm_notify_delivery}}', ['id' => $sends]) : 0,
    ]);
};
$saved = (new \yii\db\Query())->from($rotation)->andWhere(['cms_site_id' => $siteId, 'queue' => InboundQueue::QUEUE_DEFAULT])->one();
$restore = static function () use ($db, $rotation, $siteId, $saved) {
    $db->createCommand()->delete($rotation, ['cms_site_id' => $siteId, 'queue' => InboundQueue::QUEUE_DEFAULT])->execute();
    if ($saved) {
        $db->createCommand()->insert($rotation, $saved)->execute();
    }
};

$series = static function (string $name) use ($processes, $members, $run, $siteId): array {
    $jobs = [];
    for ($i = 1; $i <= $processes - 3; $i++) {
        $jobs[] = 'ok|'.$run.'-'.$name.'-'.$i;
    }
    $jobs[] = 'rollback|'.$run.'-'.$name.'-rollback';
    $jobs[] = 'ok|'.$run.'-'.$name.'-twice';
    $jobs[] = 'ok|'.$run.'-'.$name.'-twice';
    $at = microtime(true) + 3;
    $running = [];
    foreach ($jobs as $job) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, 'child', $job, (string)$at], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null,
            ['INBOUND_QUEUE_TEST' => 'local', 'QUEUE_MEMBERS' => $members, 'TEST_APP_ROOT' => getenv('TEST_APP_ROOT') ?: '/app']);
        $running[] = [$job, $process, $pipes];
    }
    $results = [];
    foreach ($running as [$job, $process, $pipes]) {
        $out = trim(stream_get_contents($pipes[1]));
        $err = trim(stream_get_contents($pipes[2]));
        $results[] = ['job' => $job, 'exit' => proc_close($process), 'out' => $out, 'err' => $err];
    }

    return $results;
};

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures) {
    if (!$ok) {
        $failures[] = $message;
    }
};

try {
    $cleanup();
    $summary = [];
    foreach (['new', 'existing'] as $name) {
        if ($name === 'new') {
            // A new queue: no row of the turn yet.
            $db->createCommand()->delete($rotation, ['cms_site_id' => $siteId, 'queue' => InboundQueue::QUEUE_DEFAULT])->execute();
        }
        $results = $series($name);
        foreach ($results as $result) {
            $check($result['exit'] === 0, $name.': '.$result['job'].' failed: '.$result['err']);
        }
        CmsLead::getDb()->schema->refresh();
        $leads = CmsLead::find()->andWhere(['cms_site_id' => $siteId])->andWhere(['like', 'source_ref', 'inbound-queue-load:'.$run.'-'.$name.'-'])
            ->orderBy(['id' => SORT_ASC])->all();
        $byRef = [];
        foreach ($leads as $lead) {
            $byRef[$lead->source_ref] = ($byRef[$lead->source_ref] ?? 0) + 1;
        }
        for ($i = 1; $i <= $processes - 3; $i++) {
            $check(($byRef['inbound-queue-load:'.$run.'-'.$name.'-'.$i] ?? 0) === 1, $name.': request '.$i.' saved '.($byRef['inbound-queue-load:'.$run.'-'.$name.'-'.$i] ?? 0).' times');
        }
        $check(($byRef['inbound-queue-load:'.$run.'-'.$name.'-twice'] ?? 0) === 1, $name.': one event sent twice is one deal');
        $check(!isset($byRef['inbound-queue-load:'.$run.'-'.$name.'-rollback']), $name.': the rolled back request is not saved');
        $executors = array_map(static fn(CmsLead $lead) => (int)$lead->executor_id, $leads);
        $alternates = true;
        for ($i = 1; $i < count($executors); $i++) {
            $alternates = $alternates && $executors[$i] !== $executors[$i - 1];
        }
        $check($alternates && !in_array(0, $executors, true), $name.': the turn alternates over the saved deals: '.implode(',', $executors));
        foreach ($leads as $lead) {
            $rows = CmsWebNotify::find()->andWhere(['model_code' => $lead->skeeksModelCode, 'model_id' => (int)$lead->id])
                ->select(['cms_user_id', 'n' => 'COUNT(*)'])->groupBy('cms_user_id')->asArray()->all();
            $perUser = array_column($rows, 'n', 'cms_user_id');
            $check((int)($perUser[(int)$lead->executor_id] ?? 0) === 1 && max(array_map('intval', $perUser ?: [0])) === 1,
                $name.': one notification per person for deal '.(int)$lead->id.' '.json_encode($perUser));
        }
        $counts = array_count_values($executors);
        $summary[] = $name.': '.count($leads).' deals '.json_encode($counts);
    }
    if ($failures) {
        throw new RuntimeException("FAIL:\n".implode("\n", $failures));
    }
    echo 'OK inbound-queue-load: '.$processes.' processes x 2 series ('.implode('; ', $summary).")\n";
} finally {
    $left = $cleanup();
    $restore();
    if ($left) {
        fwrite(STDERR, 'FAIL inbound-queue-load: left after cleaning up '.json_encode($left)."\n");
        exit(1);
    }
    echo "Cleaned up: no deals, contacts, feed, access journal, notifications or deliveries of run {$run} are left\n";
}
