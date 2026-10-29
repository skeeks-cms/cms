<?php
/**
 * The migrations of «Полноценная работа в рамках своих сделок» (29.10.2026) on a SEPARATE MySQL
 * database, by the rule «Repeatable up, honest down» (skeeks-cms-package-development,
 * references/package-migrations.md): cms_deal.cms_lead_id (skeeks/cms) and shop_bill/shop_document
 * .cms_lead_id (cms-shop) resume after a run stopped at any statement, a repeated up changes nothing,
 * down restores the schema, and a rollback that would erase the links to deals is refused before DDL.
 *
 * The database (TEST_DB_NAME, skeeks_leadlinks by default) gets empty copies of the tables from the
 * stand database (TEST_DB_SOURCE, skeeks_local) and is dropped at the end. The stand database is never changed.
 *
 * CRM_LEAD_LINKS_MIGRATIONS_TEST=local php tests/crm-lead-links-migrations-stand.php
 *   TEST_DB_HOST (db), TEST_DB_USER (root), TEST_DB_PASSWORD (root), TEST_SHOP_MIGRATIONS (cms-shop migrations dir)
 */
if (getenv('CRM_LEAD_LINKS_MIGRATIONS_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_LEAD_LINKS_MIGRATIONS_TEST=local.');
}
$root = getenv('TEST_APP_ROOT') ?: '/app';
require $root.'/vendor/autoload.php';
require $root.'/vendor/yiisoft/yii2/Yii.php';
$harness = require __DIR__.'/support/migration-interrupt.php';
$host = getenv('TEST_DB_HOST') ?: 'db';
$user = getenv('TEST_DB_USER') ?: 'root';
$password = getenv('TEST_DB_PASSWORD') ?: 'root';
$name = getenv('TEST_DB_NAME') ?: 'skeeks_leadlinks';
$source = getenv('TEST_DB_SOURCE') ?: 'skeeks_local';
if ($name === $source) {
    throw new RuntimeException('The test database must not be the stand database.');
}
$shopDir = getenv('TEST_SHOP_MIGRATIONS') ?: dirname(__DIR__, 2).'/cms-shop/src/migrations';

$migrations = [
    'm261029_190000__alter_table__cms_deal_cms_lead' => [dirname(__DIR__).'/src/migrations', ['cms_deal']],
    'm261029_190000__alter_tables__shop_bill_document_cms_lead' => [$shopDir, ['shop_bill', 'shop_document']],
];

$server = new PDO('mysql:host='.$host, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$server->exec("DROP DATABASE IF EXISTS `$name`; CREATE DATABASE `$name` CHARACTER SET utf8mb4");
$fail = 0;
$expect = static function (bool $ok, string $what) use (&$fail) { $fail += $ok ? 0 : 1; echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n"; };
try {
    foreach (['cms_lead', 'cms_deal', 'shop_bill', 'shop_document'] as $table) {
        $server->exec("CREATE TABLE `$name`.`$table` LIKE `$source`.`$table`");
    }
    new yii\console\Application(['id' => 'leadlinks', 'basePath' => $root, 'components' => ['db' => [
        'class' => yii\db\Connection::class, 'dsn' => 'mysql:host='.$host.';dbname='.$name, 'username' => $user, 'password' => $password, 'charset' => 'utf8mb4']]]);
    $db = Yii::$app->db;
    foreach ($migrations as $class => [$dir]) {
        require_once $dir.'/'.$class.'.php';
    }

    // The schema before them: their own rollbacks on the empty copies (CREATE TABLE LIKE copies the column and its index).
    foreach (array_reverse(array_keys($migrations)) as $class) {
        $error = $harness->run($db, $class, 'safeDown');
        $expect($error === null, "$class: down to the schema before it".($error ? " — $error" : ''));
    }
    // Every interruption point.
    foreach ($migrations as $class => [, $tables]) {
        $harness->check($db, $class, $tables, $expect);
    }

    // Honest down: a link to a deal is never erased by a rollback; the schema stays.
    foreach ($migrations as $class => [, $tables]) {
        $harness->run($db, $class, 'safeUp');
    }
    $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
    // A row of the empty copy: the given values and a neutral one for every required column without a default.
    $insert = static function (string $table, array $row) use ($db) {
        foreach ($db->getTableSchema($table, true)->columns as $column) {
            if (!array_key_exists($column->name, $row) && !$column->allowNull && $column->defaultValue === null && !$column->autoIncrement) {
                $row[$column->name] = $column->phpType === 'string' ? '' : 0;
            }
        }
        $db->createCommand()->insert($table, $row)->execute();
    };
    $insert('cms_lead', ['id' => 1, 'name' => 'Проверка: сделка']);
    foreach ([['cms_deal', 'm261029_190000__alter_table__cms_deal_cms_lead', ['name' => 'Проверка: услуга', 'cms_deal_type_id' => 1]],
                 ['shop_bill', 'm261029_190000__alter_tables__shop_bill_document_cms_lead', ['code' => 'check-bill']],
                 ['shop_document', 'm261029_190000__alter_tables__shop_bill_document_cms_lead', ['type' => 'act', 'status' => 'draft']]] as [$table, $class, $row]) {
        $tables = $migrations[$class][1];
        $insert($table, $row + ['cms_lead_id' => 1]);
        $snapshot = $harness->snapshot($db, $tables);
        $error = $harness->run($db, $class, 'safeDown');
        $expect($error !== null && strpos($error, 'Откат невозможен') !== false && $harness->snapshot($db, $tables) === $snapshot,
            "$class: a $table linked to a deal — rollback refused before DDL, the schema stays");
        $db->createCommand()->delete($table)->execute();
    }
    foreach (array_reverse(array_keys($migrations)) as $class) {
        $error = $harness->run($db, $class, 'safeDown');
        $expect($error === null, "$class: without links the rollback goes".($error ? " — $error" : ''));
    }
} finally {
    $server->exec("DROP DATABASE IF EXISTS `$name`");
}
echo $fail ? "FAIL crm-lead-links-migrations-stand: $fail\n" : "OK crm-lead-links-migrations-stand\n";
exit($fail ? 1 : 0);
