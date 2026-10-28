<?php
/**
 * Release audit F06 (29.10.2026): the rollbacks of the CRM migrations whose DDL would destroy data —
 * the journal of the access, duplicate marks and requests, access levels, main responsibles — are
 * refused before the first DDL while such data exist; the schema stays. Never on the main snapshot:
 * the test makes its own database (TEST_DB_NAME, skeeks_f06 by default) with copies of the CRM tables
 * from the stand database (TEST_DB_SOURCE, skeeks_local), runs the rollbacks there and drops it.
 *
 * CRM_MIGRATIONS_ROLLBACK_TEST=local php tests/crm-migrations-rollback-stand.php
 *   TEST_DB_HOST (db), TEST_DB_USER (root), TEST_DB_PASSWORD (root)
 */
if (getenv('CRM_MIGRATIONS_ROLLBACK_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_MIGRATIONS_ROLLBACK_TEST=local.');
}
$root = getenv('TEST_APP_ROOT') ?: '/app';
require $root.'/vendor/autoload.php';
require $root.'/vendor/yiisoft/yii2/Yii.php';
$host = getenv('TEST_DB_HOST') ?: 'db';
$user = getenv('TEST_DB_USER') ?: 'root';
$password = getenv('TEST_DB_PASSWORD') ?: 'root';
$name = getenv('TEST_DB_NAME') ?: 'skeeks_f06';
$source = getenv('TEST_DB_SOURCE') ?: 'skeeks_local';
if ($name === $source) {
    throw new RuntimeException('The test database must not be the stand database.');
}
$server = new PDO('mysql:host='.$host, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$server->exec("DROP DATABASE IF EXISTS `$name`; CREATE DATABASE `$name` CHARACTER SET utf8mb4");
foreach ([
    'cms_crm_access_log' => 'LIMIT 3', 'cms_crm_duplicate_mark' => 'LIMIT 2', 'cms_crm_access_request' => 'LIMIT 2',
    'cms_lead' => 'WHERE triaged_at IS NOT NULL LIMIT 2', 'cms_company2manager' => "WHERE access_level <> 'full' LIMIT 2",
    'cms_user2manager' => 'WHERE is_main = 1 LIMIT 2', 'cms_company' => 'LIMIT 0', 'cms_user' => 'LIMIT 0',
] as $table => $rows) {
    $server->exec("CREATE TABLE `$name`.`$table` LIKE `$source`.`$table`");
    $server->exec("INSERT INTO `$name`.`$table` SELECT * FROM `$source`.`$table` $rows");
}
new yii\console\Application(['id' => 'f06', 'basePath' => $root, 'components' => ['db' => [
    'class' => yii\db\Connection::class, 'dsn' => 'mysql:host='.$host.';dbname='.$name, 'username' => $user, 'password' => $password, 'charset' => 'utf8mb4']]]);
$db = Yii::$app->db;
$dir = dirname(__DIR__).'/src/migrations/';
$run = static function (string $migrationName, string $method) use ($dir, $db) {
    require_once $dir.$migrationName.'.php';
    $migration = new $migrationName(['db' => $db, 'compact' => true]);
    ob_start();
    try {
        $migration->$method();
        $error = null;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
    ob_end_clean();
    return $error;
};
$columns = static fn(string $table) => $db->getTableSchema($table, true) ? count($db->getTableSchema($table, true)->columns) : 0;
$fail = 0;
$expect = static function (bool $ok, string $what) use (&$fail) { $fail += $ok ? 0 : 1; echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n"; };

// With data: every rollback is refused before any DDL, the schema stays.
foreach ([
    'm261029_130000__create_table__cms_crm_access_log' => ['cms_crm_access_log'],
    'm261028_100000__alter_table__cms_lead_triage' => ['cms_crm_duplicate_mark', 'cms_crm_access_request', 'cms_lead'],
    'm261028_110000__alter_table__crm_access_levels' => ['cms_company2manager', 'cms_user2manager', 'cms_crm_access_request'],
    'm261028_140000__alter_table__crm_main_responsible' => ['cms_company2manager', 'cms_user2manager', 'cms_company', 'cms_user'],
] as $migrationName => $tables) {
    $before = array_map($columns, $tables);
    $error = $run($migrationName, 'safeDown');
    $expect($error !== null && strpos($error, 'Откат невозможен') === 0 && array_map($columns, $tables) === $before,
        "$migrationName: refused with data, the schema unchanged".($error ? ' — '.mb_substr($error, 0, 110) : ''));
}
// Without the data the journal rollback works, and up brings the table back.
$db->createCommand()->delete('cms_crm_access_log')->execute();
$expect($run('m261029_130000__create_table__cms_crm_access_log', 'safeDown') === null && $columns('cms_crm_access_log') === 0, 'the empty journal: rollback drops the table');
$run('m261029_130000__create_table__cms_crm_access_log', 'safeUp');
$expect($columns('cms_crm_access_log') > 0, 'up creates it again');
$db->close();
$server->exec("DROP DATABASE `$name`");
if ($fail) {
    echo "FAIL: $fail\n";
    exit(1);
}
echo "OK crm-migrations-rollback-stand\n";
