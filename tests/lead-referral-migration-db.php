<?php
/**
 * m261105_100000 («Откуда узнали») on a SEPARATE MySQL database: every interruption point of the
 * migration (support/migration-interrupt.php), the starting list for every site, a repeated run adds
 * nothing and keeps what people changed, the rollback is refused while deals have the field or people
 * added or changed items, and goes through on the starting list alone.
 *
 * The database (TEST_DB_NAME, skeeks_referral by default) gets empty copies of the tables from the stand
 * database (TEST_DB_SOURCE, skeeks_local) and the stand sites; it is dropped at the end.
 *
 * LEAD_REFERRAL_MIGRATION_TEST=local php tests/lead-referral-migration-db.php
 *   TEST_DB_HOST (db), TEST_DB_USER (root), TEST_DB_PASSWORD (root)
 */
if (getenv('LEAD_REFERRAL_MIGRATION_TEST') !== 'local') {
    throw new RuntimeException('Set LEAD_REFERRAL_MIGRATION_TEST=local.');
}
$root = getenv('TEST_APP_ROOT') ?: '/app';
require $root.'/vendor/autoload.php';
require $root.'/vendor/yiisoft/yii2/Yii.php';
$harness = require __DIR__.'/support/migration-interrupt.php';
$host = getenv('TEST_DB_HOST') ?: 'db';
$user = getenv('TEST_DB_USER') ?: 'root';
$password = getenv('TEST_DB_PASSWORD') ?: 'root';
$name = getenv('TEST_DB_NAME') ?: 'skeeks_referral';
$source = getenv('TEST_DB_SOURCE') ?: 'skeeks_local';
if ($name === $source) {
    throw new RuntimeException('The test database must not be the stand database.');
}
$class = 'm261105_100000__create_table__cms_lead_referral';
$tables = ['cms_lead_referral', 'cms_lead'];

$server = new PDO('mysql:host='.$host, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$server->exec("DROP DATABASE IF EXISTS `$name`; CREATE DATABASE `$name` CHARACTER SET utf8mb4");
$fail = 0;
$expect = static function (bool $ok, string $what) use (&$fail) { $fail += $ok ? 0 : 1; echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n"; };
try {
    foreach (['cms_site', 'cms_user', 'cms_lead', 'cms_lead_referral'] as $table) {
        $server->exec("CREATE TABLE `$name`.`$table` LIKE `$source`.`$table`");
    }
    $server->exec("INSERT INTO `$name`.`cms_site` SELECT * FROM `$source`.`cms_site`");
    new yii\console\Application(['id' => 'referral', 'basePath' => $root, 'components' => ['db' => [
        'class' => yii\db\Connection::class, 'dsn' => 'mysql:host='.$host.';dbname='.$name, 'username' => $user, 'password' => $password, 'charset' => 'utf8mb4']]]);
    $db = Yii::$app->db;
    require_once dirname(__DIR__).'/src/migrations/'.$class.'.php';
    $sites = (int)$db->createCommand('SELECT COUNT(*) FROM cms_site')->queryScalar();
    $count = static fn() => $db->getTableSchema('cms_lead_referral', true) ? (int)$db->createCommand('SELECT COUNT(*) FROM cms_lead_referral')->queryScalar() : -1;

    // Back to the schema before the migration (empty copies: nothing to protect).
    $error = $harness->run($db, $class, 'safeDown');
    $expect($error === null && !$db->getTableSchema('cms_lead_referral', true) && !$db->getTableSchema('cms_lead', true)->getColumn('cms_lead_referral_id'),
        'down on empty copies: the schema before the migration'.($error ? " — $error" : ''));

    // Every interruption point; the migration stays applied.
    $points = $harness->check($db, $class, $tables, $expect);
    $expect($points > 5, "interruption points checked: $points");
    $expect($count() === $sites * 11, 'the starting list for every site: '.$count().' of '.($sites * 11));

    // A repeated run adds nothing and keeps what people changed.
    $db->createCommand()->update('cms_lead_referral', ['name' => 'Директ (свой)', 'match_rules' => 'utm_source=ya', 'updated_by' => null], ['code' => 'yandex_direct'])->execute();
    $error = $harness->run($db, $class, 'safeUp');
    $expect($error === null && $count() === $sites * 11, 'up again: no duplicates');
    $expect((int)$db->createCommand("SELECT COUNT(*) FROM cms_lead_referral WHERE name = 'Директ (свой)' AND match_rules = 'utm_source=ya'")->queryScalar() === $sites,
        'up again: renamed items stay as people left them');

    // The rollback is refused while deals have the field, and while people added or changed items.
    $siteId = (int)$db->createCommand('SELECT id FROM cms_site ORDER BY id LIMIT 1')->queryScalar();
    $itemId = (int)$db->createCommand('SELECT id FROM cms_lead_referral WHERE cms_site_id = :s ORDER BY id LIMIT 1', [':s' => $siteId])->queryScalar();
    $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
    $db->createCommand()->insert('cms_lead', ['id' => 900101, 'name' => 'Проверка: откуда узнали', 'cms_site_id' => $siteId, 'cms_lead_referral_id' => $itemId,
        'source_type' => 'manual', 'status' => 'new'])->execute();
    $error = $harness->run($db, $class, 'safeDown');
    $expect($error !== null && strpos($error, 'Откат невозможен') !== false && $db->getTableSchema('cms_lead_referral', true) !== null,
        'down with a deal that has the field: refused, the schema stays');
    $db->createCommand()->update('cms_lead', ['cms_lead_referral_id' => null], ['id' => 900101])->execute();
    $db->createCommand()->update('cms_lead_referral', ['updated_by' => 1], ['id' => $itemId])->execute();
    $error = $harness->run($db, $class, 'safeDown');
    $expect($error !== null && strpos($error, 'изменённые людьми') !== false, 'down with an item changed by a person: refused');
    $db->createCommand()->update('cms_lead_referral', ['updated_by' => null], ['id' => $itemId])->execute();
    $error = $harness->run($db, $class, 'safeDown');
    $expect($error === null && !$db->getTableSchema('cms_lead_referral', true), 'down with the starting list alone: goes through'.($error ? " — $error" : ''));
    $error = $harness->run($db, $class, 'safeUp');
    $expect($error === null && $count() === $sites * 11, 'up after down: the starting list again');
} finally {
    $server->exec("DROP DATABASE IF EXISTS `$name`");
}
echo $fail ? "FAILED: $fail\n" : "OK lead-referral-migration-db\n";
exit($fail ? 1 : 0);
