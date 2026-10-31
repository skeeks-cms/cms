<?php
/**
 * Task starts on the stand database, rolled back (owner's decision 31.10.2026): a fixed task keeps its time
 * and takes it out of the schedule; auto tasks go in queue order into the nearest free working time,
 * a long one continues after a break; a manual reorder; a start set in a form fixes the task, an emptied
 * one gives it back to the planner; the past is refused; the nearest free window and the estimate.
 *
 * CMS_TASK_PLANNER_STAND_TEST=local php tests/task-planner-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CMS_TASK_PLANNER_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CMS_TASK_PLANNER_STAND_TEST=local.');
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

use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\TaskPlanner;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};

$transaction = Yii::$app->db->beginTransaction();
try {
    Yii::$app->skeeks->site = CmsSite::find()->default()->one();
    $check(TaskPlanner::hasStartMode(), 'the migration of the start mode is applied');

    $user = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->one();
    $check((bool)$user, 'the stand has an employee');
    Yii::$app->user->setIdentity($user);
    // Only the tasks of this test: the employee's own active tasks are put aside inside the transaction.
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => (int)$user->id, 'status' => CmsTask::activeStatuses()]);
    $user->work_shedule = [
        ['day' => [1, 2, 3, 4, 5], 'startHour' => '09', 'startMinutes' => '00', 'endHour' => '13', 'endMinutes' => '00'],
        ['day' => [1, 2, 3, 4, 5], 'startHour' => '14', 'startMinutes' => '00', 'endHour' => '18', 'endMinutes' => '00'],
    ];

    $tz = new DateTimeZone(Yii::$app->formatter->timeZone);
    $monday = (new DateTime('next monday', $tz))->modify('+7 days');
    $at = static function (string $time) use ($monday, $tz) {
        return (new DateTime($monday->format('Y-m-d').' '.$time, $tz))->getTimestamp();
    };
    $hm = static fn($ts) => Yii::$app->formatter->asTime((int)$ts, 'php:H:i');

    $make = static function (string $name, int $minutes, ?int $start = null) use ($user) {
        $task = new CmsTask(['name' => $name, 'executor_id' => (int)$user->id, 'plan_duration' => $minutes * 60]);
        if ($start) {
            $task->plan_start_at = $start;
        }
        if (!$task->save()) {
            throw new RuntimeException($name.': '.implode(' ', $task->getFirstErrors()));
        }
        return $task;
    };

    $fixed = $make('Звонок в 10:00', 30, $at('10:00'));
    $check((int)$fixed->is_fixed_start === 1, 'a start set by a person fixes the task');
    $a = $make('A', 60);
    $b = $make('B', 30);
    $cTask = $make('C', 15);
    $d = $make('D', 180);
    $check((int)$a->is_fixed_start === 0, 'a task without a start is auto');

    $planner = new TaskPlanner();
    $planner->now = $at('09:00');
    $planner->plan($user);
    $get = static fn(CmsTask $t) => CmsTask::findOne($t->id);

    $check($hm($get($fixed)->plan_start_at) === '10:00', 'the fixed task keeps its time');
    $check($hm($get($a)->plan_start_at) === '09:00' && $hm($get($a)->plan_end_at) === '10:00', 'A fills 9:00–10:00');
    $check($hm($get($b)->plan_start_at) === '10:30', 'B goes after the fixed call: 10:30, got '.$hm($get($b)->plan_start_at));
    $check($hm($get($cTask)->plan_start_at) === '11:00', 'C right after B');
    $check($hm($get($d)->plan_start_at) === '11:15' && $hm($get($d)->plan_end_at) === '15:15', 'D continues after the lunch break: 11:15–15:15, got '.$hm($get($d)->plan_end_at));
    $check((int)$get($a)->executor_sort === 1 && (int)$get($d)->executor_sort === 4, 'queue numbers follow the order');

    // A noticeable move of an auto task is marked with its former start; a small shift is not.
    CmsTask::updateAll(['replanned_from' => null, 'replanned_at' => null], ['executor_id' => (int)$user->id]);
    CmsTask::updateAll(['plan_start_at' => $at('09:00')], ['id' => (int)$fixed->id]);
    $planner->plan($user);
    $check($hm($get($a)->plan_start_at) === '09:30' && (int)$get($a)->replanned_from === $at('09:00'), 'A moved by half an hour shows its former start');
    $check(strpos($get($a)->replannedText, 'Перенесена по графику с') === 0 && $get($a)->replannedIconHtml !== '', 'the note «перенесена» is drawn');
    CmsTask::updateAll(['plan_start_at' => $at('10:00')], ['id' => (int)$fixed->id]);
    CmsTask::updateAll(['replanned_from' => null, 'replanned_at' => null], ['executor_id' => (int)$user->id]);
    $planner->plan($user);
    $moved = $get($a);
    $moved->plan_start_at = $at('16:00');
    $check($moved->save() && $get($a)->replanned_from === null && (int)$get($a)->is_fixed_start === 1, 'a person choosing the time removes the note');
    CmsTask::updateAll(['is_fixed_start' => 0], ['id' => (int)$a->id]);

    $planner->plan($user, [(int)$cTask->id, (int)$a->id]);
    $check($hm($get($cTask)->plan_start_at) === '09:00', 'a manual reorder puts C first');
    $check($hm($get($a)->plan_start_at) === '09:15' && $hm($get($a)->plan_end_at) === '10:45', 'A is split around the fixed call: ends 10:45');

    $empty = new TaskPlanner();
    $empty->now = $at('09:00');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => [(int)$a->id, (int)$b->id, (int)$cTask->id, (int)$d->id]]);
    $check($empty->nearestStart($user, 60 * 60, $at('09:00')) === $at('09:00'), 'one free hour before the fixed call: 9:00');
    $check($empty->nearestStart($user, 90 * 60, $at('09:00')) === $at('10:30'), 'an hour and a half does not fit before 10:00: 10:30');
    [$estStart, $estEnd] = $empty->estimate($user, 30 * 60);
    $check($estStart === $at('09:00') && $estEnd === $at('09:30'), 'the estimate of a new auto task');

    $fixedReload = $get($fixed);
    $fixedReload->plan_start_at = null;
    $check($fixedReload->save(), 'an emptied start saves');
    $fixedReload = $get($fixed);
    $check((int)$fixedReload->is_fixed_start === 0 && (int)$fixedReload->plan_start_at > 0, 'an emptied start gives the task back to the planner, which sets a start');

    $past = $get($fixed);
    $past->plan_start_at = time() - 3600;
    $check(!$past->validate(['plan_start_at']), 'the past is refused');
    $soon = $get($fixed);
    $soon->plan_start_at = time() + 5 * 60;
    $check($soon->validate(['plan_start_at']), 'any near future time is allowed (no 30 minute limit)');

    $fixedReload->name = 'Только название';
    $check($fixedReload->save() && (int)$get($fixed)->is_fixed_start === 0, 'saving other fields keeps an auto task auto');
} finally {
    $transaction->rollBack();
}

echo "OK {$checks} checks\n";
