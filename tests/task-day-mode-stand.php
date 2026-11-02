<?php
/**
 * The start mode «на день» (owner's decision 02.11.2026), on the stand database, rolled back:
 * - a day is laid out: fixed tasks, then the day's tasks «на день» in queue order, then auto tasks;
 * - an overloaded day continues after its working time, the tasks keep their day, auto tasks go on the next day;
 * - an unfinished task of a past day waits today, first, and keeps its day («Просрочена на N дн.»);
 * - «Когда» of the form: '' — по графику, a date — на день, a date and a time — закреплена; a past day is refused;
 * - the pin of a task «на день» fixes it on its time and the day goes; setStartDay() puts any task «на день».
 *
 * CMS_TASK_DAY_TEST=local php tests/task-day-mode-stand.php
 */
if (getenv('CMS_TASK_DAY_TEST') !== 'local') {
    throw new RuntimeException('Set CMS_TASK_DAY_TEST=local.');
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
use skeeks\cms\models\CmsTaskSchedule;
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
    $check(TaskPlanner::hasPlanDay(), 'the migration of plan_day is applied');
    Yii::$app->skeeks->site = CmsSite::find()->default()->one();
    $user = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->one();
    Yii::$app->user->setIdentity($user);
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => (int)$user->id, 'status' => CmsTask::activeStatuses()]);
    CmsTaskSchedule::updateAll(['end_at' => time() - 60], ['cms_user_id' => (int)$user->id, 'end_at' => null]);
    // Weekdays 9–18 for this test (the layout reads the user object it is given).
    $user->work_shedule = [['day' => [1, 2, 3, 4, 5], 'startHour' => '09', 'startMinutes' => '00', 'endHour' => '18', 'endMinutes' => '00']];

    $f = Yii::$app->formatter;
    $tz = new DateTimeZone($f->timeZone);
    $monday = (new DateTime('next monday', $tz))->modify('+14 days');
    $day = $monday->format('Y-m-d');
    $at = static function (string $time, int $days = 0) use ($monday, $tz) {
        return (new DateTime($monday->format('Y-m-d').' '.$time, $tz))->modify('+'.$days.' days')->getTimestamp();
    };
    $hm = static fn($time) => $f->asTime((int)$time, 'php:H:i');
    $make = static function (string $name, int $minutes, array $attributes = []) use ($user) {
        $task = new CmsTask(array_merge(['name' => $name, 'executor_id' => (int)$user->id, 'plan_duration' => $minutes * 60], $attributes));
        if (!$task->save()) {
            throw new RuntimeException($name.': '.implode(' ', $task->getFirstErrors()));
        }
        return CmsTask::findOne($task->id);
    };
    $get = static fn(CmsTask $t) => CmsTask::findOne($t->id);
    $planner = new TaskPlanner();
    $plan = static function (string $now) use ($planner, $user, $at) {
        $planner->now = $at($now);
        $planner->plan($user);
    };

    // 1. The day: the fixed one, then «на день» in queue order, then auto.
    $fixed = $make('Звонок', 60, ['plan_start_at' => $at('10:00')]);
    $a = $make('На день А', 60, ['plan_day' => $day]);
    $b = $make('На день Б', 60, ['plan_day' => $day]);
    $auto = $make('По графику', 60);
    $check($get($a)->startMode === 'day' && (int)$get($a)->is_fixed_start === 0 && $get($fixed)->startMode === 'fixed' && $get($auto)->startMode === 'auto',
        'three modes: fixed, day, auto');
    $plan('08:00');
    $check($hm($get($fixed)->plan_start_at) === '10:00', 'the fixed task keeps 10:00');
    $check($hm($get($a)->plan_start_at) === '09:00' && $hm($get($b)->plan_start_at) === '11:00',
        'the day tasks fill the free time of their day around the fixed one: '.$hm($get($a)->plan_start_at).', '.$hm($get($b)->plan_start_at));
    $check($hm($get($auto)->plan_start_at) === '12:00' && $f->asDate((int)$get($auto)->plan_start_at, 'php:Y-m-d') === $day,
        'the auto task takes what is left after them: '.$f->asDatetime((int)$get($auto)->plan_start_at, 'php:Y-m-d H:i'));

    // 2. An overloaded day: the day tasks go on after 18:00 and keep their day; the auto task moves to the next day.
    $big = $make('На день большая', 7 * 60, ['plan_day' => $day]);
    $plan('08:00');
    $check($f->asDate((int)$get($big)->plan_start_at, 'php:Y-m-d') === $day && (string)$get($big)->plan_day === $day,
        'an overloaded day keeps its task «на день»');
    $check((int)$get($big)->plan_end_at > $at('18:00'), 'the rest goes on after the working time: '.$f->asDatetime((int)$get($big)->plan_end_at, 'php:H:i'));
    $overload = $planner->dayOverload($user, $day, [$get($fixed), $get($a), $get($b), $get($big), $get($auto)]);
    $check($overload === (int)$get($big)->plan_end_at - $at('18:00') && $overload > 0, 'the day shows how much it is overloaded: '.$overload);
    $check($f->asDate((int)$get($auto)->plan_start_at, 'php:Y-m-d') === $monday->modify('+1 day')->format('Y-m-d') && $hm($get($auto)->plan_start_at) === '09:00',
        'the auto task goes to the next day: '.$f->asDatetime((int)$get($auto)->plan_start_at, 'php:Y-m-d H:i'));
    $monday->modify('-1 day');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => (int)$big->id]);

    // 3. An unfinished task of a past day waits today, first, before today's own; its day stays.
    $next = $monday->modify('+1 day')->format('Y-m-d');
    $monday->modify('-1 day');
    $late = $make('Вчерашняя', 30, ['plan_day' => $next]);
    CmsTask::updateAll(['plan_day' => $day], ['id' => (int)$late->id]);
    $c1 = $make('Сегодняшняя', 30, ['plan_day' => $next]);
    // «Today» is the next day now: every task of the past day (А, Б and this one) waits today, before today's own.
    $planner->now = $at('08:00', 1);
    $planner->plan($user);
    $check((string)$get($late)->plan_day === $day && $f->asDate((int)$get($late)->plan_start_at, 'php:Y-m-d') === $next
        && (int)$get($late)->plan_start_at < (int)$get($c1)->plan_start_at && $hm($get($a)->plan_start_at) === '09:00'
        && $f->asDate((int)$get($a)->plan_start_at, 'php:Y-m-d') === $next,
        'the overdue tasks wait today first: '.$f->asDatetime((int)$get($late)->plan_start_at, 'php:Y-m-d H:i'));
    $check((int)$get($c1)->plan_start_at < (int)$get($auto)->plan_start_at, 'today\'s own day tasks go before the auto ones');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => [(int)$late->id, (int)$c1->id]]);
    $planner->now = null;

    // 4. «Когда» of the form.
    $task = $get($auto);
    $task->startWhen = $day;
    $check($task->save() && $get($task)->startMode === 'day' && (string)$get($task)->plan_day === $day, '«Когда» = a date: на день');
    $task = $get($task);
    $task->startWhen = $day.' 15:30';
    $check($task->save() && $get($task)->startMode === 'fixed' && (int)$get($task)->plan_start_at === $at('15:30') && $get($task)->plan_day === null,
        '«Когда» = a date and a time: закреплена, the day goes');
    $task = $get($task);
    $task->startWhen = '';
    $check($task->save() && $get($task)->startMode === 'auto', '«Когда» empty: по графику');
    $task = $get($task);
    $task->startWhen = $f->asDate(time() - 3 * 86400, 'php:Y-m-d');
    $check(!$task->save() && $task->hasErrors('plan_day'), 'a past day is refused');
    $check($get($task)->startMode === 'auto' && $get($task)->getStartWhen() === '', 'nothing saved with the refusal');
    $check($get($a)->getStartWhen() === $day && $get($fixed)->getStartWhen() === $day.' 10:00', 'the form shows the date of a day task and the time of a fixed one');

    // 5. The pin of a task «на день» fixes it on its time; setStartDay() puts a fixed task «на день».
    $check($get($a)->setFixedStart(true) === null && $get($a)->startMode === 'fixed' && $get($a)->plan_day === null, 'the pin fixes a day task, the day goes');
    $check($get($fixed)->setStartDay($day) === null && $get($fixed)->startMode === 'day' && (int)$get($fixed)->is_fixed_start === 0, 'setStartDay(): a fixed task becomes «на день»');
    $check($get($b)->getDayChipHtml() !== '' && strpos($get($b)->getDayChipHtml(), 'sx-task-day-chip') !== false && $get($a)->getDayChipHtml() === '',
        'the day chip shows only on a task «на день»');
    CmsTask::updateAll(['status' => CmsTask::STATUS_READY], ['id' => (int)$b->id]);
    $check($get($b)->setStartDay($next) !== null, 'a closed task is not put «на день»');

    // 6. The one-time move of fixed tasks: calls, meetings and tasks with a reminder keep the pin.
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => (int)$user->id, 'status' => CmsTask::activeStatuses()]);
    $plan1 = $make('Статья: плитка для ванной', 60, ['plan_start_at' => $at('11:00', 2)]);
    $call = $make('Перезвонить клиенту', 15, ['plan_start_at' => $at('12:00', 2)]);
    $meet = $make('Встреча в офисе', 60, ['plan_start_at' => $at('15:00', 2)]);
    $reminded = $make('Сдать отчёт', 30, ['plan_start_at' => $at('16:00', 2), 'reminder' => '900']);
    $old = $make('Старый план', 30, ['plan_start_at' => $at('10:00', 2)]);
    CmsTask::updateAll(['plan_start_at' => time() - 3 * 86400], ['id' => (int)$old->id]);
    $migration = new skeeks\cms\services\TaskDayMigration();
    $migration->userId = (int)$user->id;
    $summary = $migration->plan()[(int)$user->id] ?? [];
    $check(($summary['move'] ?? 0) === 2 && ($summary['keep'] ?? 0) === 3 && ($summary['past'] ?? 0) === 1,
        'the dry run: 2 move (one of a passed day), 3 keep the pin '.json_encode($summary, JSON_UNESCAPED_UNICODE));
    $check($get($plan1)->startMode === 'fixed', 'the dry run changes nothing');
    $check($migration->apply() === 2, 'apply moves two');
    $check($get($plan1)->startMode === 'day' && (string)$get($plan1)->plan_day === $f->asDate($at('11:00', 2), 'php:Y-m-d'), 'a plan becomes «на день» of its date');
    $check($get($old)->startMode === 'day' && $get($old)->overdueDays === 3 && $f->asDate((int)$get($old)->plan_start_at, 'php:Y-m-d') === $f->asDate(time(), 'php:Y-m-d'),
        'a task of a passed day waits today, overdue by 3 days');
    $check($get($call)->startMode === 'fixed' && $get($meet)->startMode === 'fixed' && $get($reminded)->startMode === 'fixed', 'calls, meetings and reminders keep the pin');
    $check($migration->apply() === 0, 'a repeated run changes nothing');

    // 7. The mass action «Перевести на день»: a task the person may not manage is refused, nothing changes.
    $multi = static function (array $ids) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->request->setUrl('/~sxx/cms/admin-cms-task/to-day-multi');
        Yii::$app->request->setBodyParams(['pk' => $ids]);
        $result = Yii::$app->createController('cms/admin-cms-task')[0]->runAction('to-day-multi');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        return $result;
    };
    $answer = $multi([(int)$call->id]);
    $check(!empty($answer['data']['success']) && $get($call)->startMode === 'day', 'the mass action puts a task «на день» '.json_encode($answer, JSON_UNESCAPED_UNICODE));
    foreach (CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->andWhere(['!=', CmsUser::tableName().'.id', (int)$user->id])->limit(20)->all() as $viewer) {
        Yii::$app->user->setIdentity($viewer);
        if (Yii::$app->user->can('cms/admin-task/manage', ['model' => $get($meet)])) {
            continue;
        }
        $answer = $multi([(int)$meet->id]);
        Yii::$app->user->setIdentity($user);
        $check(!empty($answer['data']['errors']) && $get($meet)->startMode === 'fixed', 'without the right to manage the task it is refused');
        $rightsChecked = true;
        break;
    }
    Yii::$app->user->setIdentity($user);
    echo empty($rightsChecked) ? "SKIP rights: every employee here manages the tasks\n" : '';
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

echo "OK {$checks} checks\n";
