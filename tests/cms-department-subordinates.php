<?php
// Subordinates of a department head (sub-departments included) against disposable SQLite,
// plus source checks that task visibility and "Исполнитель" filters use the same rule.
// Run: php tests/cms-department-subordinates.php /app/vendor/autoload.php
namespace {
    define('YII_ENABLE_ERROR_HANDLER', false);
    require $argv[1];
    require dirname($argv[1]).'/yiisoft/yii2/Yii.php';

    function departmentSubordinatesExpect($condition, $message)
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    $app = new \yii\console\Application(['id' => 'department-subordinates-test', 'basePath' => __DIR__, 'components' => [
        'db' => ['class' => \yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
    ]]);
    $db = $app->db;
    $db->createCommand('CREATE TABLE cms_department (id INTEGER PRIMARY KEY, pid INTEGER, name TEXT, worker_id INTEGER, sort INTEGER)')->execute();
    $db->createCommand('CREATE TABLE cms_department2worker (id INTEGER PRIMARY KEY, cms_department_id INTEGER, worker_id INTEGER)')->execute();

    // SkeekS (head 1) ─┬─ Sitika (head 300) ─┬─ Продажи (head 110) ── Подотдел (head 500)
    //                  │                      └─ Контент (no head)
    //                  └─ Бухгалтерия (head 23)
    $db->createCommand()->batchInsert('cms_department', ['id', 'pid', 'name', 'worker_id', 'sort'], [
        [1, null, 'SkeekS', 1, 100],
        [5, 1, 'Sitika', 300, 100],
        [7, 5, 'Продажи', 110, 100],
        [8, 5, 'Контент', null, 100],
        [10, 7, 'Подотдел', 500, 100],
        [4, 1, 'Бухгалтерия', 23, 100],
    ])->execute();
    $db->createCommand()->batchInsert('cms_department2worker', ['cms_department_id', 'worker_id'], [
        [1, 23],
        [7, 231], [7, 237], [7, 1663], [7, 110], // the head listed among own workers is not his own subordinate
        [8, 400],
        [10, 501],
        [4, 600],
    ])->execute();

    $ids = static fn(int $id) => \skeeks\cms\models\CmsDepartment::subordinateIds($id);

    departmentSubordinatesExpect($ids(110) === [231, 237, 500, 501, 1663], 'Head of a department: own workers, sub-department head and workers. Got '.json_encode($ids(110)));
    departmentSubordinatesExpect($ids(300) === [110, 231, 237, 400, 500, 501, 1663], 'Head of a parent department: all nested heads and workers. Got '.json_encode($ids(300)));
    departmentSubordinatesExpect($ids(500) === [501], 'Head of a leaf department. Got '.json_encode($ids(500)));
    departmentSubordinatesExpect($ids(231) === [], 'An ordinary worker has no subordinates. Got '.json_encode($ids(231)));
    departmentSubordinatesExpect($ids(23) === [600], 'Another branch is not visible: a head sees only own departments. Got '.json_encode($ids(23)));
    departmentSubordinatesExpect($ids(1) === [23, 110, 231, 237, 300, 400, 500, 501, 600, 1663], 'Root head sees the whole tree except himself. Got '.json_encode($ids(1)));
    departmentSubordinatesExpect($ids(0) === [], 'No user, no subordinates.');

    $src = dirname(__DIR__).'/src';
    $user = file_get_contents($src.'/models/User.php');
    departmentSubordinatesExpect(strpos($user, 'CmsDepartment::subordinateIds((int)$this->id)') !== false, 'User::getSubordinates() must use CmsDepartment::subordinateIds().');

    $taskQuery = file_get_contents($src.'/models/queries/CmsTaskQuery.php');
    departmentSubordinatesExpect(strpos($taskQuery, '".executor_id" => array_values($managers)') !== false, 'CmsTaskQuery::forManager() must show tasks executed by subordinates.');
    departmentSubordinatesExpect(strpos($taskQuery, '".created_by" => array_values($managers)') !== false, 'CmsTaskQuery::forManager() must show tasks created by subordinates.');

    $userQuery = file_get_contents($src.'/models/queries/CmsUserQuery.php');
    departmentSubordinatesExpect(strpos($userQuery, 'function managedBy(') !== false && strpos($userQuery, 'CmsDepartment::subordinateIds((int)$user->id)') !== false, 'CmsUserQuery::managedBy() must use the subordinates rule.');

    $controller = file_get_contents($src.'/controllers/AdminCmsTaskController.php');
    $report = file_get_contents($src.'/views/admin-cms-task/report.php');
    departmentSubordinatesExpect(strpos($controller, '$q = CmsUser::find()->managedBy();') !== false, 'Task list "Исполнитель" filter must offer only self and subordinates.');
    departmentSubordinatesExpect(strpos($report, '$query = CmsUser::find()->managedBy();') !== false, 'Task report "Исполнитель" filter must offer only self and subordinates.');

    echo "OK: department head subordinates with nested departments and their heads, other branches hidden, task visibility and executor filters use the rule\n";
}
