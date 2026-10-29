<?php
/**
 * «По кругу» (п. 13б, crm\InboundQueue::next()): two requests at the same
 * moment never go to one employee. Two real processes take the turn of a
 * separate test queue ("race-test", not the site's one): the first holds the
 * lock of the turn for two seconds in its transaction, the second waits for it
 * and gets the next employee. The test row is removed at the end.
 *
 * Usage: INBOUND_QUEUE_TEST=local php tests/inbound-queue-race.php
 *        (QUEUE_MEMBERS=1663,110 by default: employees with the deals right)
 */

if (getenv('INBOUND_QUEUE_TEST') !== 'local') {
    throw new RuntimeException('Set INBOUND_QUEUE_TEST=local.');
}
$role = $argv[1] ?? 'main';
$members = getenv('QUEUE_MEMBERS') ?: '1663,110';
const RACE_QUEUE = 'race-test';

if ($role === 'main') {
    $start = static function (string $role) use ($members) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, $role], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null,
            ['INBOUND_QUEUE_TEST' => 'local', 'QUEUE_MEMBERS' => $members, 'TEST_APP_ROOT' => getenv('TEST_APP_ROOT') ?: '/app']);

        return [$process, $pipes, microtime(true)];
    };
    $finish = static function (array $run): array {
        [$process, $pipes, $started] = $run;
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        proc_close($process);

        return [trim($out), trim($err), microtime(true) - $started];
    };
    $first = $start('hold');
    usleep(500000);
    $second = $start('take');
    [$firstOut, $firstErr] = $finish($first);
    [$secondOut, $secondErr, $secondTime] = $finish($second);
    // Clean: the test queue row only.
    $finish($start('clean'));
    if (!preg_match('/^USER (\d+)$/m', $firstOut, $a) || !preg_match('/^USER (\d+)$/m', $secondOut, $b)) {
        throw new RuntimeException("FAIL: no result\nfirst: $firstOut $firstErr\nsecond: $secondOut $secondErr");
    }
    if ($a[1] === $b[1]) {
        throw new RuntimeException('FAIL: two requests at the same moment went to one employee '.$a[1]);
    }
    if ($secondTime < 1.2) {
        throw new RuntimeException('FAIL: the second request did not wait for the lock ('.round($secondTime, 2).' s)');
    }
    echo 'OK inbound-queue-race: '.$a[1].' then '.$b[1].', the second waited '.round($secondTime, 1)." s\n";
    exit(0);
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

$siteId = (int)Yii::$app->skeeks->site->id;
if ($role === 'clean') {
    Yii::$app->db->createCommand()->delete('{{%cms_crm_inbound_rotation}}', ['cms_site_id' => $siteId, 'queue' => RACE_QUEUE])->execute();
    echo "CLEAN\n";
    exit(0);
}
$settings = Yii::$app->crmSettings;
$settings->inboundWorkerIds = array_map('intval', explode(',', $members));
$settings->inboundMode = \skeeks\cms\crm\InboundQueue::MODE_ROUND;
\skeeks\cms\crm\InboundQueue::reset();

$transaction = Yii::$app->db->beginTransaction();
$userId = \skeeks\cms\crm\InboundQueue::next($siteId, RACE_QUEUE);
if ($role === 'hold') {
    // The deal of the first request is still being saved: the turn stays locked.
    sleep(2);
}
$transaction->commit();
echo 'USER '.$userId."\n";
