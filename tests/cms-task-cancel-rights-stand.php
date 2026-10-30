<?php
/**
 * Who may cancel a task on the stand database, rolled back: its executor, its
 * author and an administrator cancel a new or accepted task and resume a
 * cancelled one through TaskWorkflow (administration and API); another
 * employee without administration access may not.
 *
 * CMS_TASK_CANCEL_STAND_TEST=local php tests/cms-task-cancel-rights-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CMS_TASK_CANCEL_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CMS_TASK_CANCEL_STAND_TEST=local.');
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
use skeeks\cms\rbac\CmsManager;
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
    $isAdmin = static fn(CmsUser $user) => Yii::$app->authManager->checkAccess((int)$user->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
    $workers = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->all();
    $admins = array_values(array_filter($workers, $isAdmin));
    $plain = array_values(array_filter($workers, static fn(CmsUser $user) => !$isAdmin($user)));
    $check(count($admins) >= 1 && count($plain) >= 2, 'the stand has an administrator and two employees without administration access');
    [$admin, $executor, $stranger] = [$admins[0], $plain[0], $plain[1]];

    $as = static function (CmsUser $user) {
        Yii::$app->user->setIdentity($user);
    };
    $newTask = static function () use ($executor, $as) {
        $as($executor);
        $task = new CmsTask(['name' => 'Проверка отмены', 'executor_id' => (int)$executor->id]);
        if (!$task->save()) {
            throw new RuntimeException('task: '.implode(' ', $task->getFirstErrors()));
        }
        return $task;
    };
    $workflow = new TaskWorkflow();
    $tryCancel = static function (CmsTask $task, CmsUser $user, string $status = CmsTask::STATUS_CANCELED) use ($as, $workflow) {
        $as($user);
        try {
            $workflow->transition($task, $status);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    };

    $task = $newTask();
    $check($task->status === CmsTask::STATUS_ACCEPTED, 'a task to oneself is accepted');
    $check(!$task->canCancel((int)$stranger->id), 'another employee may not cancel');
    $check(!$tryCancel($task, $stranger), 'TaskWorkflow refuses another employee');
    $check($task->refresh() && $task->status === CmsTask::STATUS_ACCEPTED, 'the task stays accepted');

    $check($task->canCancel((int)$admin->id), 'an administrator may cancel');
    $check($tryCancel($task, $admin), 'TaskWorkflow lets an administrator cancel');
    $check($task->refresh() && $task->status === CmsTask::STATUS_CANCELED, 'the task is cancelled');
    $check(!$tryCancel($task, $stranger, CmsTask::STATUS_ON_PAUSE), 'another employee may not resume a cancelled task');
    $check($tryCancel($task, $admin, CmsTask::STATUS_ON_PAUSE), 'an administrator resumes a cancelled task');

    $task = $newTask();
    $check($tryCancel($task, $executor), 'the executor (and author) still cancels');

    echo "OK: {$checks} checks\n";
} finally {
    $transaction->rollBack();
}
