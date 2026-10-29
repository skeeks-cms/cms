<?php
/**
 * Release audit N4/N5 (29.10.2026) on a SEPARATE MySQL database: the new DDL migrations of the CRM
 * resume after a run stopped at any statement (MySQL does not roll DDL back, the history row is
 * written after safeUp), a repeated up keeps what users changed meanwhile, and a rollback that would
 * erase «Сумма» of deals or the pipelines with the stage history is refused before the first DDL.
 *
 * The database (TEST_DB_NAME, skeeks_n45 by default) gets empty copies of the tables from the stand
 * database (TEST_DB_SOURCE, skeeks_local), is brought back to the schema before the CRM migrations by
 * their own safeDown and is dropped at the end. The stand database is never changed.
 *
 * CRM_MIGRATIONS_REPEAT_TEST=local php tests/crm-migrations-repeat-stand.php
 *   TEST_DB_HOST (db), TEST_DB_USER (root), TEST_DB_PASSWORD (root)
 */
if (getenv('CRM_MIGRATIONS_REPEAT_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_MIGRATIONS_REPEAT_TEST=local.');
}
$root = getenv('TEST_APP_ROOT') ?: '/app';
require $root.'/vendor/autoload.php';
require $root.'/vendor/yiisoft/yii2/Yii.php';
$harness = require __DIR__.'/support/migration-interrupt.php';
$host = getenv('TEST_DB_HOST') ?: 'db';
$user = getenv('TEST_DB_USER') ?: 'root';
$password = getenv('TEST_DB_PASSWORD') ?: 'root';
$name = getenv('TEST_DB_NAME') ?: 'skeeks_n45';
$source = getenv('TEST_DB_SOURCE') ?: 'skeeks_local';
if ($name === $source) {
    throw new RuntimeException('The test database must not be the stand database.');
}

// The DDL migrations in their order, with the tables each one changes.
$migrations = [
    'm261028_100000__alter_table__cms_lead_triage' => ['cms_lead', 'cms_crm_access_request', 'cms_crm_duplicate_mark'],
    'm261028_110000__alter_table__crm_access_levels' => ['cms_company2manager', 'cms_user2manager', 'cms_crm_access_request'],
    'm261028_120000__alter_table__cms_lead_amount' => ['cms_lead'],
    'm261028_130000__create_table__cms_lead_pipeline' => ['cms_lead_pipeline', 'cms_lead_stage', 'cms_lead', 'cms_lead_stage_history'],
    'm261028_140000__alter_table__crm_main_responsible' => ['cms_company2manager', 'cms_user2manager', 'cms_company', 'cms_user', 'cms_crm_access_request'],
    'm261028_160000__alter_table__cms_log_model_index' => ['cms_log'],
    'm261028_170000__alter_table__crm_protection' => ['cms_company', 'cms_user'],
    'm261029_100000__alter_table__cms_lead_outbound' => ['cms_lead'],
    'm261029_130000__create_table__cms_crm_access_log' => ['cms_crm_access_log'],
    'm261029_160000__alter_table__cms_user_crm_new_always' => ['cms_user'],
];
$copy = ['cms_site', 'cms_user', 'cms_company', 'cms_company2manager', 'cms_user2manager', 'cms_lead', 'cms_lead_stage', 'cms_lead_pipeline',
    'cms_lead_stage_history', 'cms_crm_access_request', 'cms_crm_duplicate_mark', 'cms_crm_access_log', 'cms_log', 'cms_deal', 'cms_deal_type', 'shop_payment'];

$server = new PDO('mysql:host='.$host, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$server->exec("DROP DATABASE IF EXISTS `$name`; CREATE DATABASE `$name` CHARACTER SET utf8mb4");
$fail = 0;
$expect = static function (bool $ok, string $what) use (&$fail) { $fail += $ok ? 0 : 1; echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n"; };
try {
    foreach ($copy as $table) {
        $server->exec("CREATE TABLE `$name`.`$table` LIKE `$source`.`$table`");
    }
    new yii\console\Application(['id' => 'n45', 'basePath' => $root, 'components' => ['db' => [
        'class' => yii\db\Connection::class, 'dsn' => 'mysql:host='.$host.';dbname='.$name, 'username' => $user, 'password' => $password, 'charset' => 'utf8mb4']]]);
    $db = Yii::$app->db;
    $dir = dirname(__DIR__).'/src/migrations/';
    foreach (array_keys($migrations) as $class) {
        require_once $dir.$class.'.php';
    }

    // The schema before the CRM migrations: their own rollbacks on the empty copies.
    foreach (array_reverse(array_keys($migrations)) as $class) {
        $error = $harness->run($db, $class, 'safeDown');
        $expect($error === null, "$class: down to the schema before the CRM".($error ? " — $error" : ''));
    }

    // N5: every interruption point of every DDL migration.
    foreach ($migrations as $class => $tables) {
        $harness->check($db, $class, $tables, $expect);
    }

    $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
    $columns = static fn(string $table) => $db->getTableSchema($table, true) ? array_keys($db->getTableSchema($table, true)->columns) : [];

    // N4: a filled «Сумма» refuses the rollback before the DDL; without it the column goes and comes back.
    $db->createCommand()->insert('cms_lead', ['id' => 900001, 'name' => 'Проверка: сумма отката', 'amount' => 12345.67])->execute();
    $error = $harness->run($db, 'm261028_120000__alter_table__cms_lead_amount', 'safeDown');
    $expect($error !== null && strpos($error, 'Откат невозможен') !== false && in_array('amount', $columns('cms_lead'), true)
        && (string)(new yii\db\Query())->select('amount')->from('cms_lead')->where(['id' => 900001])->scalar($db) === '12345.67',
        'amount filled: rollback refused, the column and the sum stay'.($error ? ' — '.mb_substr($error, 0, 100) : ''));
    $db->createCommand()->update('cms_lead', ['amount' => null], ['id' => 900001])->execute();
    $error = $harness->run($db, 'm261028_120000__alter_table__cms_lead_amount', 'safeDown');
    $expect($error === null && !in_array('amount', $columns('cms_lead'), true), 'amount empty: rollback drops the column');
    $harness->run($db, 'm261028_120000__alter_table__cms_lead_amount', 'safeUp');
    $expect(in_array('amount', $columns('cms_lead'), true), 'amount: up brings it back');

    // N4: the stage history with unique stage names refuses the rollback of the pipelines, as do the pipelines themselves.
    $pipelineTables = $migrations['m261028_130000__create_table__cms_lead_pipeline'];
    $schemaBefore = $harness->snapshot($db, $pipelineTables);
    $db->createCommand()->insert('cms_lead_stage', ['id' => 900001, 'cms_site_id' => 1, 'name' => 'Проверка: этап A'])->execute();
    $db->createCommand()->insert('cms_lead_stage', ['id' => 900002, 'cms_site_id' => 1, 'name' => 'Проверка: этап B'])->execute();
    $db->createCommand()->insert('cms_lead_stage_history', ['cms_site_id' => 1, 'cms_lead_id' => 900001, 'created_at' => time(), 'kind' => 'stage',
        'from_status' => 'Проверка: этап A', 'to_status' => 'Проверка: этап B', 'from_stage_id' => 900001, 'to_stage_id' => 900002])->execute();
    $error = $harness->run($db, 'm261028_130000__create_table__cms_lead_pipeline', 'safeDown');
    $expect($error !== null && strpos($error, 'история этапов сделок: 1') !== false && $harness->snapshot($db, $pipelineTables) === $schemaBefore
        && (int)(new yii\db\Query())->from('cms_lead_stage_history')->count('*', $db) === 1,
        'stage history, unique stage names: rollback refused, the schema and the history stay'.($error ? ' — '.mb_substr($error, 0, 100) : ''));
    $db->createCommand()->delete('cms_lead_stage_history')->execute();
    $db->createCommand()->insert('cms_lead_pipeline', ['id' => 900001, 'cms_site_id' => 1, 'name' => 'Проверка: воронка'])->execute();
    $db->createCommand()->update('cms_lead_stage', ['cms_lead_pipeline_id' => 900001], ['id' => 900001])->execute();
    $error = $harness->run($db, 'm261028_130000__create_table__cms_lead_pipeline', 'safeDown');
    $expect($error !== null && strpos($error, 'воронки: 1') !== false && strpos($error, 'этапы в воронках: 1') !== false
        && $harness->snapshot($db, $pipelineTables) === $schemaBefore, 'a pipeline with a stage: rollback refused, the schema stays');
    $db->createCommand()->delete('cms_lead_pipeline')->execute();
    $db->createCommand()->update('cms_lead_stage', ['cms_lead_pipeline_id' => null], ['id' => 900001])->execute();
    $error = $harness->run($db, 'm261028_130000__create_table__cms_lead_pipeline', 'safeDown');
    $expect($error === null && !$db->getTableSchema('cms_lead_stage_history', true) && !$db->getTableSchema('cms_lead_pipeline', true),
        'no pipelines and no history: rollback drops them'.($error ? ' — '.$error : ''));
    $harness->run($db, 'm261028_130000__create_table__cms_lead_pipeline', 'safeUp');
    $expect($harness->snapshot($db, $pipelineTables) === $schemaBefore, 'pipelines: up brings the same schema back');

    // A repeated up keeps what users changed meanwhile.
    $db->createCommand()->insert('cms_company', ['id' => 900001, 'name' => 'Проверка: защита', 'crm_access_by_approval' => 1, 'crm_protection' => 'free'])->execute();
    $harness->run($db, 'm261028_170000__alter_table__crm_protection', 'safeUp');
    $expect((new yii\db\Query())->select('crm_protection')->from('cms_company')->where(['id' => 900001])->scalar($db) === 'free',
        'protection: a repeated up does not turn «always» on again');

    $db->createCommand()->batchInsert('cms_company2manager', ['id', 'cms_company_id', 'cms_user_id', 'is_main', 'access_level'],
        [[900001, 900001, 900001, 0, 'deal'], [900002, 900001, 900002, 1, 'full']])->execute();
    $harness->run($db, 'm261028_140000__alter_table__crm_main_responsible', 'safeUp');
    $mains = (new yii\db\Query())->select('id')->from('cms_company2manager')->where(['cms_company_id' => 900001, 'is_main' => 1])->column($db);
    $level = (new yii\db\Query())->select('access_level')->from('cms_company2manager')->where(['id' => 900001])->scalar($db);
    $expect(array_map('intval', $mains) === [900002] && $level === 'deal', 'main responsible: a repeated up keeps the chosen main and the level (main '.implode(',', $mains).', level '.$level.')');

    $db->createCommand()->insert('cms_crm_access_request', ['id' => 900001, 'created_at' => time(), 'requester_id' => 900001, 'record_code' => 'company',
        'record_id' => 900001, 'kind' => 'work', 'status' => 'approved', 'level' => 'deal', 'decided_at' => time()])->execute();
    $db->createCommand()->delete('cms_crm_access_log')->execute();
    $harness->run($db, 'm261029_130000__create_table__cms_crm_access_log', 'safeUp');
    $harness->run($db, 'm261029_130000__create_table__cms_crm_access_log', 'safeUp');
    $events = (new yii\db\Query())->select('event')->from('cms_crm_access_log')->where(['request_id' => 900001])->orderBy(['id' => SORT_ASC])->column($db);
    $expect($events === ['request', 'request_approved'], 'access journal: a repeated up copies a request once ('.implode(', ', $events).')');
} finally {
    if (isset($db)) {
        $db->close();
    }
    $server->exec("DROP DATABASE IF EXISTS `$name`");
}
if ($fail) {
    echo "FAIL: $fail\n";
    exit(1);
}
echo "OK crm-migrations-repeat-stand\n";
