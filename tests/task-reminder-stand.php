<?php
/**
 * Task reminders on the stand database, rolled back; messengers, mail and push are switched off, nothing leaves
 * the stand. A reminder «before the start» follows the start (a form choice, the planner moving an auto task);
 * a reminder whose time came goes once as an important notification to the executor and, when asked, the
 * author; a reminder the author set for themself ignores quiet hours (task.reminder_self), one set by
 * someone else waits for them (task.reminder); a moved reminder goes again; a custom time in the past is
 * refused; a reminder the check missed goes late with «опоздало на N мин», still once.
 *
 * CMS_TASK_REMINDER_STAND_TEST=local php tests/task-reminder-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CMS_TASK_REMINDER_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CMS_TASK_REMINDER_STAND_TEST=local.');
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
// Only the bell in the CRM: no messenger, mail or push leaves the stand.
$c['components']['notifications']['channels'] = ['push' => null, 'messengers' => null, 'email' => null];
unset($c['components']['notifications']['on created']);
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsWebNotify;
use skeeks\cms\notify\Notifications;
use skeeks\cms\services\TaskPlanner;
use skeeks\cms\services\TaskReminders;

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
    $check(TaskPlanner::hasReminder(), 'the reminder migration is applied');
    $kind = Yii::$app->notifications->kind('task.reminder');
    $check($kind && (int)$kind['importance'] === Notifications::IMPORTANCE_IMPORTANT && !$kind['mutable'], 'task.reminder is an important kind that cannot be muted');
    $check(!Yii::$app->notifications->ignoresQuietHours('task.reminder') && Yii::$app->notifications->ignoresQuietHours('task.reminder_self'),
        'a reminder from someone else waits for quiet hours to end, one set for oneself does not');

    $workers = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->limit(2)->all();
    $check(count($workers) === 2, 'the stand has two employees');
    [$author, $executor] = $workers;
    Yii::$app->user->setIdentity($author);

    $start = time() + 3 * 86400;
    $start -= $start % 300;
    $task = new CmsTask(['name' => 'Перезвонить клиенту', 'executor_id' => (int)$executor->id, 'plan_duration' => 900, 'plan_start_at' => $start]);
    $task->reminder = '900';
    $task->remind_author = 1;
    $check($task->save(), 'a task with a reminder saves: '.implode(' ', $task->getFirstErrors()));
    $task->refresh();
    $check((int)$task->remind_at === $start - 900 && (int)$task->remind_offset === 900, 'the reminder is 15 minutes before the start');
    $check($task->reminder === '900' && $task->hasPendingReminder(), 'the form shows the choice and the bell is pending');
    $check(strpos($task->reminderIconHtml, 'fa-bell') !== false && strpos($task->reminderIconHtml, 'is-sent') === false, 'the bell is drawn');

    $task->plan_start_at = $start + 3600;
    $check($task->save(), 'the start moves');
    $task->refresh();
    $check((int)$task->remind_at === $start + 3600 - 900, 'the reminder moves with the start');

    $reminders = new TaskReminders();
    $check($reminders->sendDue((int)$task->remind_at - 60) === 0, 'nothing is sent before the time');
    $sent = $reminders->sendDue((int)$task->remind_at + 5);
    $check($sent >= 1, 'the reminder goes when its time came');
    $task->refresh();
    $check((int)$task->reminded_at > 0 && !$task->hasPendingReminder(), 'the reminder is marked as sent');
    $toExecutor = CmsWebNotify::find()->andWhere(['cms_user_id' => (int)$executor->id, 'kind' => ['task.reminder', 'task.reminder_self'], 'model_id' => (int)$task->id])->one();
    $toAuthor = CmsWebNotify::find()->andWhere(['cms_user_id' => (int)$author->id, 'kind' => ['task.reminder', 'task.reminder_self'], 'model_id' => (int)$task->id])->one();
    $check((bool)$toExecutor && (bool)$toAuthor, 'the executor and the author get the notification');
    $check((int)$task->remind_by === (int)$author->id, 'the reminder remembers who set it');
    $check($toAuthor->kind === 'task.reminder_self' && $toExecutor->kind === 'task.reminder',
        'the author set it for themself (no quiet hours), the executor got it from someone else');
    $check(strpos((string)$toExecutor->name, 'Напоминание') === 0, 'the text says it is a reminder: '.$toExecutor->name);
    $check($reminders->sendDue((int)$task->remind_at + 120) === 0, 'a reminder goes once');

    $task->plan_start_at = $start + 2 * 3600;
    $check($task->save(), 'the start moves again');
    $task->refresh();
    $check($task->reminded_at === null && $task->hasPendingReminder(), 'a moved reminder will go again');

    // An auto task: the planner moves the start and the reminder with it.
    $auto = new CmsTask(['name' => 'Авто с напоминанием', 'executor_id' => (int)$executor->id, 'plan_duration' => 600]);
    $auto->reminder = '0';
    $check($auto->save(), 'an auto task with a reminder at its start saves');
    $auto->refresh();
    $check((int)$auto->plan_start_at > 0 && (int)$auto->remind_at === (int)$auto->plan_start_at, 'the planner set the start and the reminder at it');

    $custom = new CmsTask(['name' => 'Своё время', 'executor_id' => (int)$executor->id, 'plan_duration' => 600]);
    $custom->reminder = 'custom';
    $custom->remind_at = time() - 3600;
    $check(!$custom->validate(['remind_at']), 'a custom reminder in the past is refused');
    $custom->remind_at = time() + 7200;
    $check($custom->save(), 'a custom reminder saves');
    $custom->refresh();
    $check($custom->remind_offset === null && $custom->reminder === 'custom', 'a custom reminder does not follow the start');

    $off = CmsTask::findOne($custom->id);
    $off->reminder = '';
    $check($off->save() && CmsTask::findOne($custom->id)->remind_at === null, 'a reminder can be removed');

    // The check was down for 47 minutes: the reminder still goes, marked late, and only once.
    $missedAt = time() - 47 * 60;
    CmsTask::updateAll(['remind_at' => $missedAt, 'reminded_at' => null], ['id' => (int)$task->id]);
    $check($reminders->sendDue() === 1, 'a missed reminder is sent after the check is back');
    $late = CmsWebNotify::find()->andWhere(['cms_user_id' => (int)$executor->id, 'model_id' => (int)$task->id,
        'event_key' => 'task.reminder:'.(int)$task->id.':'.$missedAt])->one();
    $check($late && strpos((string)$late->name, 'опоздало на 47 мин') !== false, 'it says how late it is: '.($late ? $late->name : '—'));
    $check($reminders->sendDue() === 0 && (new TaskReminders())->sendDue() === 0, 'a repeated or a second run does not send it again');
} finally {
    $transaction->rollBack();
}

echo "OK {$checks} checks\n";
