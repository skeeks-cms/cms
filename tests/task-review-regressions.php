<?php
/**
 * Regressions of the independent review of the stage «Задачи» (31.10.2026), on the stand database, rolled back:
 * - F3 a closed task and the task in work do not move, refused by the model before anything is saved;
 * - F4 a started task is fixed on when its work started (a future fixed task and a future auto task);
 * - F5 a reminder of its own time follows the task: a move, a layout, unpinning; «Отменить» restores it exactly;
 * - F6 an edit of the name keeps the end the planner calculated after a break;
 * - F7 a move by another person keeps the reminder's author;
 * - F13 a start a package calculated (isAutoStart) leaves a new task auto, in the queue by that time;
 * - F11 the order of the day list refuses another executor's or a missing task;
 * - F2 a failure before the notification is stored leaves the reminder to the next run;
 * - F8 a task closed after the selection is not reminded.
 * Round 2 (01.11.2026): the pin answers only visible tasks (N1) with their new marks (N4); a schedule change waits for
 * the layout lock and is refused when it is busy (N2); the order refuses fixed tasks.
 * Two processes at once (F1, F2, F8) — tests/task-concurrency-disposable.php.
 *
 * CMS_TASK_REVIEW_TEST=local php tests/task-review-regressions.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CMS_TASK_REVIEW_TEST') !== 'local') {
    throw new RuntimeException('Set CMS_TASK_REVIEW_TEST=local.');
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
use skeeks\cms\services\TaskReminders;
use skeeks\cms\services\TaskWorkflow;

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
    $users = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->limit(2)->all();
    $check(count($users) === 2, 'the stand has two employees');
    [$user, $other] = $users;
    Yii::$app->user->setIdentity($user);
    // Only the tasks of this test: the employees' own active tasks and open intervals are put aside.
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => [(int)$user->id, (int)$other->id], 'status' => CmsTask::activeStatuses()]);
    CmsTaskSchedule::updateAll(['end_at' => time() - 60], ['cms_user_id' => (int)$user->id, 'end_at' => null]);

    $tz = new DateTimeZone(Yii::$app->formatter->timeZone);
    $monday = (new DateTime('next monday', $tz))->modify('+14 days');
    $at = static function (string $time, int $days = 0) use ($monday, $tz) {
        return (new DateTime($monday->format('Y-m-d').' '.$time, $tz))->modify('+'.$days.' days')->getTimestamp();
    };
    $make = static function (string $name, int $minutes, ?int $start = null, ?CmsUser $executor = null) use ($user) {
        $task = new CmsTask(['name' => $name, 'executor_id' => (int)($executor ?: $user)->id, 'plan_duration' => $minutes * 60]);
        if ($start) {
            $task->plan_start_at = $start;
        }
        if (!$task->save()) {
            throw new RuntimeException($name.': '.implode(' ', $task->getFirstErrors()));
        }
        return CmsTask::findOne($task->id);
    };
    $get = static fn(CmsTask $t) => CmsTask::findOne($t->id);

    // F3. A closed task and the task in work do not move, whoever saves them; nothing is saved then.
    foreach ([CmsTask::STATUS_READY, CmsTask::STATUS_IN_WORK] as $status) {
        $task = $make('Закрытая или в работе', 30, $at('10:00'));
        CmsTask::updateAll(['status' => $status], ['id' => (int)$task->id]);
        $task = $get($task);
        $task->name = 'Новое название';
        $task->plan_start_at = $at('12:00');
        $check(!$task->save() && $task->hasErrors('plan_start_at'), $status.': a new start is refused');
        $check($get($task)->name !== 'Новое название' && (int)$get($task)->plan_start_at === $at('10:00'), $status.': nothing is saved with the refusal');
        $task = $get($task);
        $task->is_fixed_start = 0;
        $check(!$task->save(), $status.': the mode does not change');
        $check($get($task)->setFixedStart(false) !== null, $status.': the pin does not change');
        $task = $get($task);
        $task->plan_duration = 3600;
        $check($task->save() === ($status === CmsTask::STATUS_IN_WORK), $status.': the duration '.($status === CmsTask::STATUS_IN_WORK ? 'of the rest of the work changes' : 'of a closed task does not change'));
        CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => (int)$task->id]);
    }

    // F4. Starting a task fixes it on now — a future fixed one and a future auto one; a resumed one keeps its first start.
    $flow = new TaskWorkflow();
    foreach (['fixed' => $at('11:00', 3), 'auto' => null] as $mode => $start) {
        $task = $make('Начать '.$mode, 30, $start);
        $check((int)$task->plan_start_at > time() + 3600 || $mode === 'auto', $mode.': planned for later');
        $before = time();
        $flow->transition($task, CmsTask::STATUS_IN_WORK);
        $task = $get($task);
        $check((int)$task->plan_start_at >= $before - 5 && (int)$task->plan_start_at <= time() + 5 && (int)$task->is_fixed_start === 1,
            $mode.': a started task is fixed on its actual start, got '.date('c', (int)$task->plan_start_at));
        // Two minutes of work, so the pause has an interval to close.
        CmsTaskSchedule::updateAll(['start_at' => time() - 120], ['cms_task_id' => (int)$task->id, 'end_at' => null]);
        $flow->transition($task, CmsTask::STATUS_ON_PAUSE);
        $first = (int)CmsTaskSchedule::find()->task($task)->min('start_at');
        CmsTaskSchedule::updateAll(['start_at' => $first - 7200, 'end_at' => $first - 3600], ['cms_task_id' => (int)$task->id]);
        $flow->transition($get($task), CmsTask::STATUS_IN_WORK);
        $check((int)$get($task)->plan_start_at === $first - 7200, $mode.': a resumed task keeps when its work first started');
        CmsTaskSchedule::updateAll(['start_at' => time() - 120], ['cms_task_id' => (int)$task->id, 'end_at' => null]);
        $flow->transition($get($task), CmsTask::STATUS_ON_PAUSE);
        CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => (int)$task->id]);
    }

    // F5, F7. A reminder of its own time follows a move by the task's executor and by another person; the author stays.
    $task = $make('Своё напоминание', 30, $at('15:00'));
    $task->reminder = 'custom';
    $task->remind_at = $at('14:45');
    $check($task->save() && (int)$get($task)->remind_by === (int)$user->id, 'the executor set the reminder');
    Yii::$app->user->setIdentity($other);
    $task = $get($task);
    $task->plan_start_at = $at('16:00');
    $check($task->save(), 'another person moves the task');
    $check((int)$get($task)->remind_at === $at('15:45'), 'the reminder moved with the task: '.date('H:i', (int)$get($task)->remind_at));
    $check((int)$get($task)->remind_by === (int)$user->id, 'F7: the reminder keeps its author after a move by another person');
    // «Отменить» brings back the former start and the former reminder exactly, sent or not.
    $task = $get($task);
    $task->restoringStart = true;
    $task->plan_start_at = $at('15:00');
    $task->is_fixed_start = 1;
    $task->remind_at = $at('14:40');
    $task->reminded_at = time() - 60;
    $check($task->save(), '«Отменить» is saved');
    $check((int)$get($task)->remind_at === $at('14:40') && (int)$get($task)->reminded_at > 0 && (int)$get($task)->remind_by === (int)$user->id,
        '«Отменить» restores the reminder and its sending state exactly, the author stays');
    CmsTask::updateAll(['reminded_at' => null, 'remind_at' => $at('14:45')], ['id' => (int)$task->id]);
    Yii::$app->user->setIdentity($user);
    // Unpinning gives the start to the planner: the reminder follows the new start too.
    $check($get($task)->setFixedStart(false) === null, 'unpinned');
    $task = $get($task);
    $check((int)$task->plan_start_at !== $at('15:00') && (int)$task->remind_at === (int)$task->plan_start_at - 900,
        'F5: after unpinning the reminder keeps 15 minutes before the new start');
    // A layout move of an auto task (the planner) moves it as well.
    $planner = new TaskPlanner();
    $blocker = $make('Закреплённая перед ней', 60, (int)$task->plan_start_at);
    $planner->plan($user);
    $task = $get($task);
    $check((int)$task->remind_at === (int)$task->plan_start_at - 900, 'F5: a layout move keeps the reminder 15 minutes before');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => [(int)$task->id, (int)$blocker->id]]);

    // F6. Editing only the name keeps the end calculated after a break.
    $user->work_shedule = [
        ['day' => [1, 2, 3, 4, 5], 'startHour' => '09', 'startMinutes' => '00', 'endHour' => '13', 'endMinutes' => '00'],
        ['day' => [1, 2, 3, 4, 5], 'startHour' => '14', 'startMinutes' => '00', 'endHour' => '18', 'endMinutes' => '00'],
    ];
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => (int)$user->id, 'status' => CmsTask::activeStatuses()]);
    $long = $make('Пять часов', 300);
    $planner->now = $at('09:00');
    $planner->plan($user);
    $end = (int)$get($long)->plan_end_at;
    $check(Yii::$app->formatter->asTime($end, 'php:H:i') === '15:00', 'the planner ends it after the break: 15:00');
    $long = $get($long);
    $long->name = 'Пять часов, новое название';
    $check($long->save() && (int)$get($long)->plan_end_at === $end, 'F6: a new name keeps the calculated end');
    $long = $get($long);
    $long->plan_duration = 6 * 3600;
    $check($long->save() && (int)$get($long)->plan_end_at !== $end, 'a new duration recalculates the end');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => (int)$long->id]);
    $planner->now = null;

    // F13. A start a package calculated leaves a new task auto, placed in the queue by that time.
    $first = $make('Очередь 1', 60);
    $second = $make('Очередь 2', 60);
    $calc = new CmsTask(['name' => 'Позвонить по КП', 'executor_id' => (int)$user->id, 'plan_duration' => 900]);
    // Calculated a little after the first task starts: its place is between the two, not after the second.
    $calc->plan_start_at = $planner->nearestStart($user, 900, (int)$get($first)->plan_start_at + 60);
    $calc->isAutoStart = true;
    $check($calc->save() && (int)$get($calc)->is_fixed_start === 0, 'F13: a calculated start leaves the new task auto');
    $check((int)$get($first)->executor_sort < (int)$get($calc)->executor_sort && (int)$get($calc)->executor_sort < (int)$get($second)->executor_sort
        && (int)$get($calc)->plan_start_at === (int)$get($first)->plan_end_at,
        'F13: it takes its place in the queue by its time (after the first, before the second), not the end of the queue');
    $person = $make('Выбранное время', 30, $at('17:00', 2));
    $check((int)$person->is_fixed_start === 1, 'a start a person gives a new task still fixes it');

    // F11. The order of the day list: an id of another executor or a missing one is refused, nothing is laid out.
    $widget = new skeeks\cms\widgets\admin\CmsWorkerTasksCalendarWidget(['id' => 'review-order', 'user' => $user]);
    $orderError = new ReflectionMethod($widget, 'orderError');
    $orderError->setAccessible(true);
    $foreign = $make('Чужая', 30, null, $other);
    $check($orderError->invoke($widget, [(int)$first->id, 2147483647]) !== null, 'F11: a missing id is refused');
    $check($orderError->invoke($widget, [(int)$first->id, (int)$foreign->id]) !== null, 'F11: another executor\'s task is refused');
    $check($orderError->invoke($widget, []) !== null, 'F11: an empty order is refused');
    $check($orderError->invoke($widget, [(int)$second->id, (int)$first->id]) === null, 'the executor\'s own tasks are accepted');

    // The pin toggles without a reload (01.11.2026): admin-cms-task/pin answers with every task of the executor whose
    // start changed, so the list moves those rows in place.
    $pinAction = static function (CmsTask $task, bool $fixed) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->request->setUrl('/~sxx/cms/admin-cms-task/pin?pk='.(int)$task->id);
        Yii::$app->request->setBodyParams(['fixed' => $fixed ? 1 : 0]);
        return Yii::$app->createController('cms/admin-cms-task')[0]->runAction('pin', ['pk' => (int)$task->id]);
    };
    // The order of the queue takes auto tasks only: a fixed, a started or a closed one is refused.
    $check($orderError->invoke($widget, [(int)$second->id, (int)$person->id]) !== null, 'F11: a fixed task is refused in the order');
    $firstStart = (int)$get($first)->plan_start_at;
    $pinned = $pinAction($first, true);
    $check(!empty($pinned['success']) && (int)$get($first)->is_fixed_start === 1 && array_column($pinned['moved'], 'id') === [(int)$first->id]
        && $pinned['moved'][0]['start'] === $firstStart,
        'pinning keeps every start: only the pinned task itself changed '.json_encode($pinned, JSON_UNESCAPED_UNICODE));
    $check($orderError->invoke($widget, [(int)$second->id, (int)$first->id]) !== null, 'F11: a pinned task leaves the order');
    $unpinned = $pinAction($person, false);
    $byId = array_column($unpinned['moved'] ?? [], null, 'id');
    $check(!empty($unpinned['success']) && isset($byId[(int)$person->id]) && $byId[(int)$person->id]['start'] === (int)$get($person)->plan_start_at
        && $byId[(int)$person->id]['time'] !== '' && $byId[(int)$person->id]['day'] !== '' && $byId[(int)$person->id]['text'] !== '',
        'unpinning answers with the new start of the task, its day and texts '.json_encode($unpinned, JSON_UNESCAPED_UNICODE));
    foreach ($unpinned['moved'] as $m) {
        $saved = CmsTask::findOne($m['id']);
        $check($m['start'] === (int)$saved->plan_start_at && $m['marks'] === $saved->startMarksHtml()
            && $m['reminder'] === ($saved->remind_at && !$saved->reminded_at ? 1 : 0), 'every changed task carries its saved start and marks');
    }
    // N4: a task the unpinned one moves gets its new marks too — the reminder follows its start.
    $marked = $make('С напоминанием', 30);
    $marked->reminder = '900';
    $check($marked->save(), 'a reminder 15 minutes before');
    $wall = $make('Закреплена на её месте', 60, (int)$get($marked)->plan_start_at);
    $markedBefore = $get($marked);
    $answer = $pinAction($wall, false);
    $byId = array_column($answer['moved'] ?? [], null, 'id');
    $check((int)$get($marked)->remind_at !== (int)$markedBefore->remind_at, 'N4: unpinning the wall moves the reminder of the task behind it');
    $check(isset($byId[(int)$marked->id]) && $byId[(int)$marked->id]['marks'] === $get($marked)->startMarksHtml()
        && strpos($byId[(int)$marked->id]['marks'], 'sx-task-bell') !== false && $byId[(int)$marked->id]['reminder'] === 1,
        'N4: the moved reminder is answered with its new bell');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => [(int)$marked->id, (int)$wall->id]]);

    // N4 in the card (round 3): pinning keeps the start but the end and «не успевает» change — the card gets them.
    // Five hours from 09:00 laid out over the lunch end at 15:00, after a deadline of 14:30; fixed, they end at 14:00.
    $lunch = $make('Пять часов с обедом', 300);
    CmsTask::updateAll(['plan_start_at' => $at('09:00', 7), 'plan_end_at' => $at('15:00', 7), 'deadline_at' => $at('14:30', 7)], ['id' => (int)$lunch->id]);
    $check($get($lunch)->isBehindDeadline(), 'N4 card: before the pin the task does not make its deadline');
    $answer = $pinAction($get($lunch), true);
    $own = array_column($answer['moved'] ?? [], null, 'id')[(int)$lunch->id] ?? null;
    $saved = $get($lunch);
    $check($own !== null && $own['start'] === $at('09:00', 7) && (int)$saved->plan_end_at !== $at('15:00', 7) && !$saved->isBehindDeadline(),
        'N4 card: the start stayed, the end moved and the deadline is made '.json_encode([$own['start'] ?? null, $saved->plan_end_at], JSON_UNESCAPED_UNICODE));
    $controller = Yii::$app->createController('cms/admin-cms-task')[0];
    $check(isset($own['card']) && $own['card']['actions'] === $controller->renderPartial('_start-state', ['model' => $saved, 'part' => 'actions'])
        && $own['card']['properties'] === $controller->renderPartial('_start-state', ['model' => $saved, 'part' => 'properties']),
        'N4 card: the own entry carries the card state of the saved task');
    $check(strpos($own['card']['actions'], Yii::$app->formatter->asDatetime((int)$saved->plan_end_at, 'php:d.m.Y H:i')) !== false
        && strpos($own['card']['actions'].$own['card']['properties'], 'sx-task-behind') === false
        && strpos($own['card']['properties'], 'Крайний срок') !== false,
        'N4 card: the new end, no «не успевает», the deadline row stays');
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => (int)$lunch->id]);

    // N1: the answer names only the tasks the person may see; the layout still counts the whole queue.
    foreach (CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->andWhere(['not in', CmsUser::tableName().'.id', [(int)$user->id]])->limit(20)->all() as $viewer) {
        $hidden = $make('Скрытая от смотрящего', 30);
        if (CmsTask::find()->forManager($viewer)->andWhere([CmsTask::tableName().'.id' => (int)$hidden->id])->exists()) {
            CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => (int)$hidden->id]);
            continue;
        }
        // A fixed task of the same executor the viewer authored (visible to him) stands right where the hidden one is.
        $authored = $make('Видимая автору', 30, (int)$get($hidden)->plan_start_at);
        CmsTask::updateAll(['created_by' => (int)$viewer->id], ['id' => (int)$authored->id]);
        Yii::$app->user->setIdentity($viewer);
        if (!Yii::$app->user->can('cms/admin-task/manage', ['model' => $get($authored)])) {
            Yii::$app->user->setIdentity($user);
            continue;
        }
        $before = (int)$get($hidden)->plan_start_at;
        $answer = $pinAction($get($authored), false);
        Yii::$app->user->setIdentity($user);
        $check(!empty($answer['success']), 'the viewer unpins his task '.json_encode($answer, JSON_UNESCAPED_UNICODE));
        $ids = array_column($answer['moved'], 'id');
        $check((int)$get($hidden)->plan_start_at !== $before, 'N1: the hidden task did move (the layout counts the whole queue)');
        $check(!in_array((int)$hidden->id, $ids, true), 'N1: the answer has no task hidden from the viewer '.json_encode($ids));
        CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['id' => [(int)$hidden->id, (int)$authored->id]]);
        $privacyChecked = true;
        break;
    }
    echo empty($privacyChecked) ? "SKIP N1: no employee here sees a task he authored but not the rest of the queue\n" : '';
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // N2: a schedule change waits for the layout lock before it is written; a busy lock refuses it, nothing is saved.
    $third = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->andWhere(['not in', CmsUser::tableName().'.id', [(int)$user->id, (int)$other->id]])->one();
    if ($third) {
        $check(!$third->isTransactional(CmsUser::OP_UPDATE), 'N2: a save without a schedule change is not made transactional');
        $saved = CmsUser::find()->select('work_shedule')->where(['id' => (int)$third->id])->scalar();
        $holder = new yii\db\Connection(['dsn' => Yii::$app->db->dsn, 'username' => Yii::$app->db->username, 'password' => Yii::$app->db->password]);
        $holder->createCommand("SELECT GET_LOCK('cms-task-plan-".(int)$third->id."', 0)")->queryScalar();
        $third->work_shedule = [['day' => [1, 2, 3], 'startHour' => '10', 'startMinutes' => '00', 'endHour' => '12', 'endMinutes' => '00']];
        $check($third->isTransactional(CmsUser::OP_UPDATE), 'N2: a schedule change is saved in a transaction with the layout');
        $began = microtime(true);
        $result = $third->save(false, ['work_shedule']);
        $holder->createCommand("SELECT RELEASE_LOCK('cms-task-plan-".(int)$third->id."')")->queryScalar();
        $holder->close();
        $check(!$result && $third->hasErrors('work_shedule') && microtime(true) - $began >= TaskPlanner::LOCK_TIMEOUT - 1,
            'N2: a busy layout lock refuses the schedule change with the message');
        $check(CmsUser::find()->select('work_shedule')->where(['id' => (int)$third->id])->scalar() === $saved, 'N2: the schedule is not written');
    }

    // F2. A failure before the notification is stored leaves the reminder to the next run.
    CmsTask::updateAll(['remind_at' => null], ['and', ['not', ['remind_at' => null]], ['reminded_at' => null]]);
    $due = $make('Напомнить', 30, $at('10:00', 4));
    CmsTask::updateAll(['remind_at' => time() - 60, 'remind_offset' => null, 'reminded_at' => null], ['id' => (int)$due->id]);
    $failing = new class extends TaskReminders {
        public $calls = 0;
        public function notify(CmsTask $task, ?int $now = null): void
        {
            $this->calls++;
            throw new RuntimeException('a temporary failure');
        }
    };
    $check($failing->sendDue() === 0 && $get($due)->reminded_at === null, 'F2: after a failure the reminder is not marked sent');
    $sender = new class extends TaskReminders {
        public $ids = [];
        public function notify(CmsTask $task, ?int $now = null): void
        {
            $this->ids[] = (int)$task->id;
        }
    };
    $check($sender->sendDue() === 1 && $sender->ids === [(int)$due->id] && $get($due)->reminded_at > 0, 'F2: the next run sends it, once');
    $check($sender->sendDue() === 0, 'and not again');

    // F8. A task closed after the selection, before the claim, is not reminded.
    CmsTask::updateAll(['reminded_at' => null, 'status' => CmsTask::STATUS_ACCEPTED], ['id' => (int)$due->id]);
    $close = static function ($event) use ($due) {
        if ((int)$event->sender->id === (int)$due->id && $event->sender->status !== CmsTask::STATUS_READY) {
            CmsTask::updateAll(['status' => CmsTask::STATUS_READY], ['id' => (int)$due->id]);
        }
    };
    yii\base\Event::on(CmsTask::class, CmsTask::EVENT_AFTER_FIND, $close);
    $sender->ids = [];
    $sender->sendDue();
    yii\base\Event::off(CmsTask::class, CmsTask::EVENT_AFTER_FIND, $close);
    $check($sender->ids === [] && $get($due)->reminded_at === null, 'F8: a task closed after the selection is not reminded');
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

echo "OK {$checks} checks\n";
