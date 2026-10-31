<?php
/**
 * Two processes at once on a DISPOSABLE copy of the database (it commits): regressions of the review of the stage
 * «Задачи» (31.10.2026).
 * - F1 two tasks created at once in transactions for one executor never get the same time; a busy layout lock
 *   is an error, not a silently skipped layout;
 * - F2 two reminder runs at once send a reminder once; a failure before the notification is stored leaves it to
 *   the next run;
 * - F8 a task being closed while the reminder run reaches it is not reminded.
 *
 * CMS_TASK_TEST_DB=<a disposable database, the name contains "test" or "review"> php tests/task-concurrency-disposable.php
 * The executor's (id 1 by default, CMS_TASK_TEST_USER) active tasks are cancelled in that copy.
 * (TEST_APP_ROOT — the application root, /app by default)
 */
$dbName = (string)getenv('CMS_TASK_TEST_DB');
if (!preg_match('/^[a-z0-9_]*(test|review)[a-z0-9_]*$/', $dbName)) {
    throw new RuntimeException('Set CMS_TASK_TEST_DB to a disposable database (its name contains "test" or "review").');
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
$c['components']['db']['dsn'] = preg_replace('/dbname=[^;]+/', 'dbname='.$dbName, $c['components']['db']['dsn']);
$c['components']['db']['enableSchemaCache'] = false;
// Nothing leaves the copy: no channels, no deliveries.
$c['components']['notifications']['channels'] = ['push' => null, 'messengers' => null, 'email' => null];
unset($c['components']['notifications']['on created']);
$c['components']['log']['targets'] = [];
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\TaskPlanner;
use skeeks\cms\services\TaskReminders;

$db = Yii::$app->db;
if ($db->createCommand('SELECT DATABASE()')->queryScalar() !== $dbName) {
    throw new RuntimeException('Not connected to the disposable database.');
}
Yii::$app->skeeks->site = CmsSite::find()->default()->one();
$userId = (int)(getenv('CMS_TASK_TEST_USER') ?: 1);
Yii::$app->user->setIdentity(CmsUser::findOne($userId));
const SPY = 'cms_task_test_delivery';

/** A reminder run that stores the reminded ids in a table of the copy instead of notifying. */
class SpyReminders extends TaskReminders
{
    public $fail = false;

    public function notify(CmsTask $task, ?int $now = null): void
    {
        if ($this->fail) {
            throw new RuntimeException('a failure before the notification is stored');
        }
        Yii::$app->db->createCommand()->insert(SPY, ['task_id' => (int)$task->id])->execute();
    }
}

$mode = $argv[1] ?? '';
if ($mode === 'create') {
    // One change in a transaction that commits half a second later.
    $transaction = $db->beginTransaction();
    $task = new CmsTask(['name' => 'Проверка: параллельное создание', 'executor_id' => $userId, 'plan_duration' => 1800]);
    if (!$task->save()) {
        $transaction->rollBack();
        echo 'ERROR '.implode(' ', $task->getFirstErrors())."\n";
        exit(1);
    }
    usleep(500000);
    $transaction->commit();
    echo 'created '.$task->id."\n";
    exit(0);
}
if ($mode === 'remind') {
    echo 'sent '.(new SpyReminders())->sendDue()."\n";
    exit(0);
}
if ($mode === 'hold') {
    $db->createCommand("SELECT GET_LOCK('cms-task-plan-".$userId."', 1)")->queryScalar();
    echo "READY\n";
    flush();
    sleep(TaskPlanner::LOCK_TIMEOUT + 2);
    exit(0);
}
if ($mode === 'close') {
    // Closes the task in a transaction that holds its row for a second.
    $transaction = $db->beginTransaction();
    CmsTask::updateAll(['status' => CmsTask::STATUS_READY], ['id' => (int)$argv[2]]);
    echo "READY\n";
    flush();
    sleep(1);
    $transaction->commit();
    exit(0);
}

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};
$child = static function (string ...$args) {
    $process = proc_open(array_merge([PHP_BINARY, __FILE__], $args), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    return [$process, $pipes];
};
$finish = static function (array $child) {
    [$process, $pipes] = $child;
    $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $out];
};

CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => $userId, 'status' => CmsTask::activeStatuses()]);
CmsTask::updateAll(['remind_at' => null], ['and', ['not', ['remind_at' => null]], ['reminded_at' => null]]);
$db->createCommand('CREATE TABLE IF NOT EXISTS '.SPY.' (task_id INT)')->execute();

// F1. Two creations at once: two different, non-overlapping times.
$a = $child('create');
$b = $child('create');
[$codeA, $outA] = $finish($a);
[$codeB, $outB] = $finish($b);
$check($codeA === 0 && $codeB === 0, 'F1: both creations succeed: '.trim($outA).' / '.trim($outB));
$rows = $db->createCommand("SELECT id, plan_start_at, plan_end_at FROM cms_task WHERE executor_id = :u AND name = 'Проверка: параллельное создание' ORDER BY plan_start_at",
    [':u' => $userId])->queryAll();
$check(count($rows) === 2 && (int)$rows[0]['plan_end_at'] <= (int)$rows[1]['plan_start_at'],
    'F1: the two tasks do not overlap: '.json_encode($rows));

// F1. A busy layout lock refuses the layout after the timeout instead of skipping it silently.
$holder = $child('hold');
$check(trim((string)fgets($holder[1][1])) === 'READY', 'another process holds the layout lock');
$started = microtime(true);
$refused = false;
try {
    (new TaskPlanner())->plan(CmsUser::findOne($userId));
} catch (yii\base\Exception $e) {
    $refused = $e->getMessage() === TaskPlanner::BUSY_MESSAGE;
}
$check($refused && microtime(true) - $started >= TaskPlanner::LOCK_TIMEOUT - 1, 'F1: a busy lock is an error after the timeout');
$finish($holder);

// F2. Two reminder runs at once: one notification.
$task = CmsTask::findOne((int)$rows[0]['id']);
CmsTask::updateAll(['remind_at' => time() - 300, 'reminded_at' => null], ['id' => (int)$task->id]);
$a = $child('remind');
$b = $child('remind');
[, $outA] = $finish($a);
[, $outB] = $finish($b);
$sent = (int)$db->createCommand('SELECT COUNT(*) FROM '.SPY.' WHERE task_id = :id', [':id' => (int)$task->id])->queryScalar();
$check($sent === 1, 'F2: two runs at once send it once: '.trim($outA).' / '.trim($outB).', stored '.$sent);

// F2. A failure before the notification is stored: not marked sent, the next run sends it.
CmsTask::updateAll(['remind_at' => time() - 300, 'reminded_at' => null], ['id' => (int)$task->id]);
$db->createCommand()->delete(SPY)->execute();
$failing = new SpyReminders();
$failing->fail = true;
$check($failing->sendDue() === 0 && CmsTask::findOne($task->id)->reminded_at === null, 'F2: after a failure the reminder is not marked sent');
$check((new SpyReminders())->sendDue() === 1 && CmsTask::findOne($task->id)->reminded_at > 0, 'F2: the next run sends it');

// F8. The task is being closed by another process while the run reaches it: no reminder.
$db->createCommand()->delete(SPY)->execute();
CmsTask::updateAll(['remind_at' => time() - 300, 'reminded_at' => null, 'status' => CmsTask::STATUS_ACCEPTED], ['id' => (int)$task->id]);
$closer = $child('close', (string)$task->id);
$check(trim((string)fgets($closer[1][1])) === 'READY', 'another process is closing the task');
$sent = (new SpyReminders())->sendDue();
$finish($closer);
$check($sent === 0 && (int)$db->createCommand('SELECT COUNT(*) FROM '.SPY)->queryScalar() === 0 && CmsTask::findOne($task->id)->reminded_at === null,
    'F8: a task closed meanwhile is not reminded');

$db->createCommand('DROP TABLE '.SPY)->execute();
echo "OK {$checks} checks\n";
