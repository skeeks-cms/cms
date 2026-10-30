<?php
/**
 * Release audit F06 (29.10.2026): the rollbacks of the CRM migrations whose DDL would destroy data —
 * the journal of the access, duplicate marks and requests, access levels, main responsibles — are
 * refused before the first DDL while such data exist; the schema stays. Never on the main snapshot:
 * the test makes its own database (TEST_DB_NAME, skeeks_f06 by default) with the structure of the CRM
 * tables of the stand database (TEST_DB_SOURCE, skeeks_local) and its own rows for every guard — no
 * row of the stand is copied, so the result does not depend on its data (audit F4-02, 30.10.2026) -
 * runs the rollbacks there and drops it in any case.
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
$fail = 0;
try {
    $tables = ['cms_crm_access_log', 'cms_crm_duplicate_mark', 'cms_crm_access_request', 'cms_lead', 'cms_company2manager', 'cms_user2manager', 'cms_company', 'cms_user'];
    foreach ($tables as $table) {
        $server->exec("CREATE TABLE `$name`.`$table` LIKE `$source`.`$table`");
    }
    // One own row: the given values, every other required column without a default filled by its type.
    $insert = static function (string $table, array $values) use ($server, $name): void {
        $columns = $server->query("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ".$server->quote($name)
            ." AND TABLE_NAME = ".$server->quote($table)." AND IS_NULLABLE = 'NO' AND COLUMN_DEFAULT IS NULL AND EXTRA NOT LIKE '%auto_increment%'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if (array_key_exists($column['COLUMN_NAME'], $values)) {
                continue;
            }
            $type = strtolower($column['DATA_TYPE']);
            if ($type === 'enum' || $type === 'set') {
                preg_match("/'([^']*)'/", $column['COLUMN_TYPE'], $first);
                $values[$column['COLUMN_NAME']] = $first[1] ?? '';
            } elseif (in_array($type, ['date', 'datetime', 'timestamp'], true)) {
                $values[$column['COLUMN_NAME']] = '2026-10-30 00:00:00';
            } elseif (in_array($type, ['char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext', 'json', 'blob', 'binary', 'varbinary'], true)) {
                $values[$column['COLUMN_NAME']] = 'Проверка: откат';
            } else {
                $values[$column['COLUMN_NAME']] = 0;
            }
        }
        $statement = $server->prepare("INSERT INTO `$name`.`$table` (`".implode('`, `', array_keys($values))."`) VALUES (".implode(', ', array_fill(0, count($values), '?')).')');
        $statement->execute(array_values($values));
    };
    $insert('cms_crm_access_log', ['comment' => 'Проверка: откат']);
    $insert('cms_crm_duplicate_mark', []);
    $insert('cms_crm_access_request', ['level' => 'deal']);
    $insert('cms_lead', ['name' => 'Проверка: откат, разобранная', 'triaged_at' => time()]);
    $insert('cms_lead', ['name' => 'Проверка: откат, не разобранная']);
    $insert('cms_company2manager', ['cms_company_id' => 1, 'cms_user_id' => 1, 'access_level' => 'deal', 'is_main' => 1]);
    $insert('cms_user2manager', ['client_id' => 1, 'worker_id' => 1, 'access_level' => 'full', 'is_main' => 1]);

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
    $expect = static function (bool $ok, string $what) use (&$fail) { $fail += $ok ? 0 : 1; echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n"; };
    $expect((int)$db->createCommand('SELECT COUNT(*) FROM cms_lead WHERE triaged_at IS NOT NULL')->queryScalar() === 1
        && (int)$db->createCommand("SELECT COUNT(*) FROM cms_company2manager WHERE access_level <> 'full'")->queryScalar() === 1, 'own rows for every guard');

    // With data: every rollback is refused before any DDL, the schema stays; the message names each kind of data found.
    foreach ([
        'm261029_130000__create_table__cms_crm_access_log' => [['cms_crm_access_log'], ['события журнала доступа: 1']],
        'm261028_100000__alter_table__cms_lead_triage' => [['cms_crm_duplicate_mark', 'cms_crm_access_request', 'cms_lead'],
            ['отметки «Это другая компания»: 1', 'запросы доступа: 1', 'разобранные сделки: 1']],
        'm261028_110000__alter_table__crm_access_levels' => [['cms_company2manager', 'cms_user2manager', 'cms_crm_access_request'],
            ['уровни доступа к компаниям ниже полного: 1', 'запросы с уровнем: 1']],
        'm261028_140000__alter_table__crm_main_responsible' => [['cms_company2manager', 'cms_user2manager', 'cms_company', 'cms_user'],
            ['основные ответственные компаний: 1', 'основные ответственные клиентов: 1']],
    ] as $migrationName => [$migrationTables, $labels]) {
        $before = array_map($columns, $migrationTables);
        $error = $run($migrationName, 'safeDown');
        $named = $error !== null && !array_filter($labels, static fn($label) => strpos($error, $label) === false);
        $expect($error !== null && strpos($error, 'Откат невозможен') === 0 && $named && array_map($columns, $migrationTables) === $before,
            "$migrationName: refused with data, the schema unchanged".($error ? ' — '.mb_substr($error, 0, 160) : ''));
    }
    // Without the data the journal rollback works, and up brings the table back.
    $db->createCommand()->delete('cms_crm_access_log')->execute();
    $expect($run('m261029_130000__create_table__cms_crm_access_log', 'safeDown') === null && $columns('cms_crm_access_log') === 0, 'the empty journal: rollback drops the table');
    $run('m261029_130000__create_table__cms_crm_access_log', 'safeUp');
    $expect($columns('cms_crm_access_log') > 0, 'up creates it again');
    $db->close();
} finally {
    $server->exec("DROP DATABASE IF EXISTS `$name`");
}
if ($fail) {
    echo "FAIL: $fail\n";
    exit(1);
}
echo "OK crm-migrations-rollback-stand\n";
