<?php
/**
 * The block «Просроченные задачи» of the list «Задачи» and the column «Просрочены» of «Сроки» (owner, 20.11.2026),
 * on the stand database, rolled back:
 * - a fixed task whose time passed before today is in the block (and in the column), counted in its title;
 * - a fixed task whose time passed today stays in today's day, red, «Просрочена»; not in the block;
 * - an auto task whose time passed is never late; a future fixed task is not late.
 *
 * CMS_TASK_OVERDUE_TEST=local php tests/task-overdue-block-stand.php
 */
if (getenv('CMS_TASK_OVERDUE_TEST') !== 'local') {
    throw new RuntimeException('Set CMS_TASK_OVERDUE_TEST=local.');
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
$c['components']['request']['url'] = '/~sxx/cms/admin-cms-task/calendar';
$c['components']['user']['enableSession'] = false;
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;

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
    $user = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->one();
    Yii::$app->user->setIdentity($user);
    CmsTask::updateAll(['status' => CmsTask::STATUS_CANCELED], ['executor_id' => (int)$user->id, 'status' => CmsTask::activeStatuses()]);

    $tz = new DateTimeZone(Yii::$app->formatter->timeZone);
    $now = time();
    $today = (new DateTime('@'.$now))->setTimezone($tz)->setTime(0, 0)->getTimestamp();
    $make = static function (string $name, int $start, int $fixed) use ($user) {
        $task = new CmsTask(['name' => $name, 'executor_id' => (int)$user->id, 'plan_duration' => 900, 'plan_start_at' => $now = time() + 86400]);
        if (!$task->save()) {
            throw new RuntimeException($name.': '.implode(' ', $task->getFirstErrors()));
        }
        // The time is put directly: a past start is not chosen in a form.
        CmsTask::updateAll(['plan_start_at' => $start, 'plan_end_at' => $start + 900, 'is_fixed_start' => $fixed], ['id' => (int)$task->id]);
        return CmsTask::findOne($task->id);
    };

    // Late yesterday at 23:55 — the case of the owner's screenshot.
    $yesterday = $make('Проверка: вчера 23:55', $today - 300, 1);
    // Late today: a minute after midnight, or two hours ago when it is later.
    $todayLate = $make('Проверка: сегодня прошло', max($today + 60, $now - 7200), 1);
    $autoLate = $make('Проверка: по графику, прошло', $today - 86400, 0);
    $future = $make('Проверка: завтра', $today + 86400 + 36000, 1);

    $check($yesterday->isLateStart($now) && $yesterday->isLateBeforeToday($now), 'yesterday 23:55: late, before today');
    if ($now - $today > 120) {
        $check($todayLate->isLateStart($now) && !$todayLate->isLateBeforeToday($now), 'a time passed today: late, not before today');
    }
    $check(!$autoLate->isLateStart($now) && !$autoLate->isLateBeforeToday($now), 'an auto task whose time passed is not late');
    $check(!$future->isLateStart($now), 'a future fixed task is not late');
    // At midnight the task of today becomes «before today».
    $check($todayLate->isLateBeforeToday($today + 86400 + 60), 'tomorrow the task late today is in the block');

    // The list «Задачи»: the block counts only the task of yesterday; the task of today is red in today's day.
    $html = skeeks\cms\widgets\admin\CmsWorkerTasksCalendarWidget::widget(['id' => 'overdue-test', 'user' => $user]);
    $check((bool)preg_match('/Просроченные задачи · (\d+)/u', $html, $m) && (int)$m[1] === 1, 'the block counts one task: '.($m[1] ?? 'no block'));
    $block = preg_match('/<table class="table sx-table sx-calendar-day sx-expired-tasks.*?<\/table>/su', $html, $b) ? $b[0] : '';
    $check(strpos($block, 'data-id="'.(int)$yesterday->id.'"') !== false, 'yesterday\'s task is in the block');
    $check(strpos($block, 'data-id="'.(int)$todayLate->id.'"') === false, 'today\'s late task is not in the block');
    $check((bool)preg_match('/<tr class="[^"]*sx-task-tr--late[^"]*"[^>]*data-id="'.(int)$todayLate->id.'"/u', $html), 'today\'s late task is red in today\'s day');
    $check(strpos($html, 'data-id="'.(int)$autoLate->id.'"') !== false && strpos($block, 'data-id="'.(int)$autoLate->id.'"') === false, 'the auto task stays in the days');

    // One look of late rows (owner, 24.11.2026): a chip «Просрочена на …» instead of «/ Просрочена!».
    $check($yesterday->getLateText($now) === 'Просрочена на 1 дн.', 'a time of yesterday: late by 1 day');
    $probe = clone $todayLate;
    $probe->plan_start_at = $now - 25 * 60;
    $check($probe->getLateText($now) === 'Просрочена на 25 мин.', 'late by minutes: '.$probe->getLateText($now));
    $probe->plan_start_at = $now - 130 * 60;
    $expect = (new DateTime('@'.($now - 130 * 60)))->setTimezone($tz) < (new DateTime('@'.$now))->setTimezone($tz)->setTime(0, 0) ? 'Просрочена на 1 дн.' : 'Просрочена на 2 ч';
    $check($probe->getLateText($now) === $expect, 'late by hours: '.$probe->getLateText($now));
    $check(strpos($html, 'Просрочена!') === false, 'no «Просрочена!» left in the list');
    if (CmsTask::getTableSchema()->getColumn('plan_day')) {
        $dayTask = $make('Проверка: на день вчера', $today - 86400 + 36000, 0);
        CmsTask::updateAll(['plan_day' => date('Y-m-d', $today - 86400)], ['id' => (int)$dayTask->id]);
        (new skeeks\cms\services\TaskPlanner())->plan($user);
        $html = skeeks\cms\widgets\admin\CmsWorkerTasksCalendarWidget::widget(['id' => 'overdue-test2', 'user' => $user]);
        $check((bool)preg_match('/<tr class="[^"]*sx-task-tr--late[^"]*"[^>]*data-id="'.(int)$dayTask->id.'"/u', $html),
            'an overdue task «на день» has the same red row');
    }

    // «Сроки»: the same rule for the column «Просрочены».
    $deadlines = skeeks\cms\widgets\admin\CmsTaskDeadlinesWidget::widget(['user' => $user]);
    $late = preg_match('/data-sx-deadlines-column="late".*?data-sx-deadlines-column="d0"/su', $deadlines, $l) ? $l[0] : '';
    $check(strpos($late, 'data-pk="'.(int)$yesterday->id.'"') !== false && strpos($late, 'data-pk="'.(int)$todayLate->id.'"') === false,
        '«Сроки»: yesterday\'s task is in «Просрочены», today\'s is not');
    $check((bool)preg_match('/sx-task-deadlines__card is-late"[^>]*data-pk="'.(int)$todayLate->id.'"/u', $deadlines), '«Сроки»: today\'s late task is red in «Сегодня»');
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

echo "OK {$checks} checks\n";
