<?php
// "Сделка как услуга": DealAccess levels, lists, recipients and client task visibility
// against a disposable SQLite database; no application DB writes.
// Run: php tests/deal-client-access.php <path to vendor/autoload.php>
namespace {
    define('YII_ENABLE_ERROR_HANDLER', false);
    require $argv[1];
    require dirname($argv[1]).'/yiisoft/yii2/Yii.php';
}
namespace skeeks\cms\models {
    class User extends \yii\db\ActiveRecord {
        public static function tableName() { return '{{%cms_user}}'; }
    }
    class CmsUser extends User {}
}
namespace {
    use skeeks\cms\deal\DealAccess;
    use skeeks\cms\models\CmsTask;
    use skeeks\cms\models\CmsUser;

    $app = new \yii\console\Application(['id' => 'deal-access-test', 'basePath' => __DIR__, 'components' => [
        'db'         => ['class' => \yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
        'dealAccess' => ['class' => DealAccess::class, 'areas' => ['chat' => ['name' => 'Чат', 'levels' => [10 => 'Читать', 20 => 'Писать']]]],
    ]]);
    $db = $app->db;
    foreach ([
        'cms_user'               => 'id INTEGER PRIMARY KEY',
        'cms_company'            => 'id INTEGER PRIMARY KEY',
        'cms_company2user'       => 'id INTEGER PRIMARY KEY, cms_company_id INTEGER, cms_user_id INTEGER, is_root INTEGER DEFAULT 0',
        'cms_deal_type'          => 'id INTEGER PRIMARY KEY, name TEXT',
        'cms_deal'               => 'id INTEGER PRIMARY KEY, cms_deal_type_id INTEGER, cms_company_id INTEGER, cms_user_id INTEGER, name TEXT, is_active INTEGER DEFAULT 1, amount REAL DEFAULT 0',
        'cms_deal_role'          => 'id INTEGER PRIMARY KEY, code TEXT, name TEXT, is_full INTEGER DEFAULT 0',
        'cms_deal_role_area'     => 'id INTEGER PRIMARY KEY, cms_deal_role_id INTEGER, area TEXT, level INTEGER',
        'cms_client_access'      => 'id INTEGER PRIMARY KEY, cms_user_id INTEGER, cms_company_id INTEGER, cms_deal_id INTEGER, scope_key TEXT, cms_deal_role_id INTEGER, is_full INTEGER DEFAULT 0, is_custom INTEGER DEFAULT 0',
        'cms_client_access_area' => 'id INTEGER PRIMARY KEY, cms_client_access_id INTEGER, area TEXT, level INTEGER',
        'cms_deal_type_area'     => 'id INTEGER PRIMARY KEY, cms_deal_type_id INTEGER, area TEXT, level INTEGER',
        'cms_deal_area'          => 'id INTEGER PRIMARY KEY, cms_deal_id INTEGER, area TEXT, level INTEGER',
        'cms_project2user'       => 'id INTEGER PRIMARY KEY, cms_project_id INTEGER, cms_user_id INTEGER',
        'cms_task'               => 'id INTEGER PRIMARY KEY, name TEXT, created_by INTEGER, cms_project_id INTEGER, cms_company_id INTEGER, cms_user_id INTEGER, cms_deal_id INTEGER, is_internal INTEGER DEFAULT 0',
    ] as $table => $columns) {
        $db->createCommand("CREATE TABLE $table ($columns)")->execute();
    }
    $ins = function ($table, $columns, $rows) use ($db) { $db->createCommand()->batchInsert($table, $columns, $rows)->execute(); };

    // People: 1 director (company-wide full), 2 marketer in deal 10, 3 accountant company-wide,
    // 4 owner of personal deal 30, 5 contact of company deal 11 without grant, 6 stranger, 7 project participant.
    $ins('cms_user', ['id'], [[1], [2], [3], [4], [5], [6], [7]]);
    $ins('cms_company', ['id'], [[100], [200]]);
    $ins('cms_deal_type', ['id', 'name'], [[1, 'Продвижение'], [2, 'Хостинг']]);
    // Type 1 may open tasks, reports, documents, billing — no "manage", no "chat"; type 2 has no rows: everything.
    $ins('cms_deal_type_area', ['cms_deal_type_id', 'area', 'level'], [[1, 'tasks', 30], [1, 'reports', 10], [1, 'documents', 10], [1, 'billing', 10]]);
    $ins('cms_deal', ['id', 'cms_deal_type_id', 'cms_company_id', 'cms_user_id', 'name'], [
        [10, 1, 100, null, 'Продвижение'],
        [11, 2, 100, 5, 'Хостинг'],
        [20, 2, 200, null, 'Чужая'],
        [30, 2, null, 4, 'DNS клиента'],
    ]);
    // Deal 11 narrows tasks to view only.
    $ins('cms_deal_area', ['cms_deal_id', 'area', 'level'], [[11, 'tasks', 10]]);
    $ins('cms_client_access', ['id', 'cms_user_id', 'cms_company_id', 'cms_deal_id', 'scope_key', 'is_full'], [
        [1, 1, 100, null, 'c:100', 1],
        [2, 2, 100, 10, 'd:10', 0],
        [3, 3, 100, null, 'c:100', 0],
        [4, 2, 100, 11, 'd:11', 0],
    ]);
    $ins('cms_client_access_area', ['cms_client_access_id', 'area', 'level'], [
        [2, 'tasks', 30], [2, 'reports', 10],
        [3, 'documents', 10], [3, 'billing', 10],
        [4, 'tasks', 30],
    ]);
    $ins('cms_project2user', ['cms_project_id', 'cms_user_id'], [[500, 7]]);
    $ins('cms_task', ['id', 'name', 'created_by', 'cms_project_id', 'cms_company_id', 'cms_user_id', 'cms_deal_id', 'is_internal'], [
        [1, 'deal 10 task', 900, null, 100, null, 10, 0],
        [2, 'deal 10 internal', 900, null, 100, null, 10, 1],
        [3, 'company only: call the client', 900, null, 100, null, null, 0],
        [4, 'client only', 900, null, null, 4, null, 0],
        [5, 'own task of 2', 2, null, 100, null, null, 0],
        [6, 'deal 11 task', 900, null, 100, null, 11, 0],
        [7, 'project task', 900, 500, 100, null, null, 0],
        [8, 'personal deal task', 900, null, null, 4, 30, 0],
        [9, 'other company deal', 900, null, 200, null, 20, 0],
        // Old rules must not bypass deal rights:
        [10, 'own task in deal, marked internal', 2, null, 100, null, 10, 1],
        [11, 'project task moved into deal 10', 900, 500, 100, null, 10, 0],
        [12, 'own task in deal without tasks right', 3, null, 100, null, 10, 0],
        [13, 'own task without deal, internal', 2, null, 100, null, null, 1],
    ]);

    $da = $app->dealAccess;
    $check = function ($label, $actual, $expected) {
        if ($actual !== $expected) {
            throw new \RuntimeException($label.': '.json_encode($actual).' !== '.json_encode($expected));
        }
    };
    $deals = function ($user, $area = null, $level = DealAccess::LEVEL_VIEW) use ($da) {
        $ids = array_map('intval', $da->dealsQuery($user, $area, $level)->select('cms_deal.id')->orderBy('cms_deal.id')->column());
        return $ids;
    };

    // Levels: grant ∩ what is open in the deal.
    $check('director 10', $da->levels(1, 10), ['tasks' => 30, 'reports' => 10, 'documents' => 10, 'billing' => 10, 'manage' => 0, 'chat' => 0]);
    // A package area ("chat") is not opened by a type without settings.
    $check('director 11', $da->levels(1, 11), ['tasks' => 10, 'reports' => 10, 'documents' => 10, 'billing' => 10, 'manage' => 20, 'chat' => 0]);
    $check('marketer 10', $da->levels(2, 10), ['tasks' => 30, 'reports' => 10, 'documents' => 0, 'billing' => 0, 'manage' => 0, 'chat' => 0]);
    $check('marketer 11 narrowed', $da->level(2, 11, 'tasks'), 10);
    $check('accountant 10', $da->levels(3, 10), ['tasks' => 0, 'reports' => 0, 'documents' => 10, 'billing' => 10, 'manage' => 0, 'chat' => 0]);
    $check('owner 30', $da->levels(4, 30), ['tasks' => 30, 'reports' => 10, 'documents' => 10, 'billing' => 10, 'manage' => 20, 'chat' => 0]);
    $db->createCommand()->insert('cms_deal_type_area', ['cms_deal_type_id' => 2, 'area' => 'chat', 'level' => 10])->execute();
    // An explicit row opens the package area, and then the type limits the others: core areas without rows close.
    $check('type 2 opens chat explicitly', $da->levels(4, 30), ['tasks' => 0, 'reports' => 0, 'documents' => 0, 'billing' => 0, 'manage' => 0, 'chat' => 10]);
    $db->createCommand()->delete('cms_deal_type_area', ['cms_deal_type_id' => 2])->execute();
    $check('contact without grant', $deals(5), []);
    $check('stranger', $deals(6), []);
    $check('unknown area', $da->can(1, 10, 'nope'), false);
    $check('level above max', $da->can(1, 10, 'reports', 20), false);

    // Lists agree with checks and do not duplicate deals.
    $check('director deals', $deals(1), [10, 11]);
    $check('director billing', $deals(1, 'billing'), [10, 11]);
    $check('director manage', $deals(1, 'manage'), [11]);
    $check('marketer deals', $deals(2), [10, 11]);
    $check('marketer create tasks', $deals(2, 'tasks', DealAccess::LEVEL_FULL), [10]);
    $check('owner deals', $deals(4), [30]);
    $check('count = distinct', (int)$da->dealsQuery(2)->count(), 2);

    // forClient() without an area: full access only — older screens never widen to limited roles.
    $legacy = function ($userId) {
        return array_map('intval', \skeeks\cms\models\CmsDeal::find()->forClient(CmsUser::findOne($userId))->select('cms_deal.id')->orderBy('cms_deal.id')->column());
    };
    $check('legacy director', $legacy(1), [10, 11]);
    $check('legacy marketer', $legacy(2), []);
    $check('legacy accountant', $legacy(3), []);
    $check('legacy owner', $legacy(4), [30]);
    $check('legacy contact without grant', $legacy(5), []);

    // Recipients.
    $users = function ($dealId, $area, $level = DealAccess::LEVEL_VIEW) use ($da) {
        $deal = \skeeks\cms\models\CmsDeal::find()->andWhere(['cms_deal.id' => $dealId])->one();
        $ids = array_map('intval', $da->usersQuery($deal, $area, $level)->select('cms_user.id')->orderBy('cms_user.id')->column());
        return $ids;
    };
    $check('billing 10 recipients', $users(10, 'billing'), [1, 3]);
    $check('tasks 10 recipients', $users(10, 'tasks'), [1, 2]);
    $check('manage 10 closed by type', $users(10, 'manage'), []);
    $check('owner recipient', $users(30, 'billing'), [4]);

    // Client tasks.
    $tasks = function ($userId, $level = DealAccess::LEVEL_VIEW) {
        $ids = array_map('intval', CmsTask::find()->forClient(CmsUser::findOne($userId), $level)->select('cms_task.id')->orderBy('cms_task.id')->column());
        return $ids;
    };
    $check('director tasks', $tasks(1), [1, 6, 11, 12]);
    $check('marketer tasks', $tasks(2), [1, 5, 6, 11, 12]);
    $check('marketer may comment', $tasks(2, DealAccess::LEVEL_ACT), [1, 5, 11, 12]);
    $check('accountant: own deal task needs the tasks right', $tasks(3), []);
    $check('owner tasks', $tasks(4), [8]);
    $check('project participant: only tasks without a deal', $tasks(7), [7]);
    $check('stranger tasks', $tasks(6), []);

    echo "OK: levels as grant ∩ deal/type openness, company-wide and deal grants, owner of a personal deal, contacts without grants, package areas, lists without duplicates, recipients, client task visibility (internal and company-only tasks hidden, comment level)\n";
}
