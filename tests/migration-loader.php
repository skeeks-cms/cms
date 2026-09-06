<?php
/** Standalone integration test. Uses SQLite in memory, never the site's DB. */
require (getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor/autoload.php';
require (getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor/yiisoft/yii2/Yii.php';

use skeeks\cms\console\controllers\MigrateController;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

class MigrationLoaderProbe extends MigrateController
{
    public function pending() { return $this->getNewMigrations(); }
    public function history() { return $this->getMigrationHistory(null); }
    public function stdout($string) { return strlen($string); }
}

$root = sys_get_temp_dir().'/skeeks-migration-loader-'.bin2hex(random_bytes(8));
$checks = 0;
function check($condition, $label) {
    global $checks;
    if (!$condition) { throw new RuntimeException($label); }
    $checks++;
    echo "PASS {$label}\n";
}
function fixture($directory, $name, $namespace = '') {
    FileHelper::createDirectory($directory);
    $prefix = $namespace ? "namespace {$namespace};\n" : '';
    $source = "<?php\n{$prefix}/* namespace not_a_declaration; */\nclass {$name} extends \\yii\\db\\Migration {\n"
        ."public function safeUp() { \$this->createTable('{$name}', ['id' => \$this->primaryKey()]); }\n"
        ."public function safeDown() { \$this->dropTable('{$name}'); }\n}\n";
    file_put_contents($directory.'/'.$name.'.php', $source);
}
function probe(array $config = []) {
    return new MigrationLoaderProbe('migrate', Yii::$app->getModule('cms'), array_merge(['interactive' => false], $config));
}
function prepare($controller) { $controller->beforeAction($controller->createAction('up')); return $controller; }
function rejects(array $config, $message) {
    try { prepare(probe($config)); } catch (InvalidConfigException $e) {
        check(strpos($e->getMessage(), $message) !== false, 'diagnostic: '.$message);
        return;
    }
    throw new RuntimeException('Expected rejection: '.$message);
}

try {
    $legacy = 'm260101_000001_legacy';
    $project = 'm260101_000002_project';
    $modern = 'M260101000003Modern';
    fixture($root.'/legacy/migrations', $legacy);
    fixture($root.'/console/migrations', $project);
    fixture($root.'/modern/migrations', $modern, 'migrationfixture\migrations');
    new yii\console\Application([
        'id' => 'migration-loader-test', 'basePath' => $root,
        'vendorPath' => (getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor',
        'components' => ['db' => ['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:']],
        'modules' => ['cms' => ['class' => yii\base\Module::class]],
        'controllerMap' => ['migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'migrationPath' => [$root.'/legacy/migrations', $root.'/legacy/../legacy/migrations'],
            'migrationNamespaces' => ['migrationfixture\migrations'],
        ]],
        'extensions' => [
            ['alias' => ['@legacy' => $root.'/legacy']],
            ['alias' => ['@migrationfixture' => $root.'/modern']],
        ],
        'aliases' => ['@console' => $root.'/console', '@migrationfixture' => $root.'/modern'],
    ]);
    $db = Yii::$app->db;
    $sourceHash = hash_file('sha256', $root.'/legacy/migrations/'.$legacy.'.php');
    $controller = prepare(probe());
    check(count($controller->migrationPath) === 2, 'canonical paths deduplicate alias discovery and registration');
    check($controller->pending() === [$legacy, $project, 'migrationfixture\\migrations\\'.$modern], 'native chronological order, including namespaces');
    check(!is_dir($root.'/runtime/db-migrate'), 'no runtime migration copies');

    // Simulate a deployed site's existing legacy identity in the normal history.
    $db->createCommand()->insert('migration', ['version' => $legacy, 'apply_time' => 1])->execute();
    check($controller->pending() === [$project, 'migrationfixture\\migrations\\'.$modern], 'already applied legacy migration is not replayed');
    check($controller->runAction('up') === 0, 'up applies legacy and namespaced sources');
    check($controller->pending() === [], 'second up has nothing to apply');
    check(isset($controller->history()[$legacy]), 'old history key remains unchanged');
    check(isset($controller->history()['migrationfixture\\migrations\\'.$modern]), 'new namespace stored as FQCN');
    check($controller->runAction('down', [1]) === 0, 'native down loads namespaced class');
    check($db->schema->getTableSchema($modern, true) === null, 'down removes fixture table');
    check($controller->runAction('up') === 0, 'namespaced migration can be reapplied');
    check(hash_file('sha256', $root.'/legacy/migrations/'.$legacy.'.php') === $sourceHash, 'migration source is not rewritten');
    check($controller->runAction('down', [2]) === 0, 'native down also loads non-namespaced source');
    check($db->schema->getTableSchema($project, true) === null, 'legacy fixture down ran');
    check($controller->runAction('up') === 0, 'both migration styles can be reapplied together');

    $isolated = prepare(probe(['migrationPath' => null]));
    check($isolated->migrationPath === null, 'null disables all non-namespaced discovery');
    check($isolated->migrationNamespaces === ['migrationfixture\migrations'], 'null retains registered namespaces');

    $cli = probe();
    $cli->runAction('new', ['migrationPath' => $root.'/console/migrations', 'migrationNamespaces' => []]);
    check($cli->migrationPath === [$root.'/console/migrations'], 'CLI path selection is not expanded');
    check($cli->migrationNamespaces === [], 'CLI namespace selection overrides package registration');

    $explicit = prepare(probe(['autoDiscoverMigrations' => false, 'useApplicationMigrationConfig' => false]));
    check($explicit->migrationPath === [$root.'/console/migrations'], 'explicit-only configuration disables compatibility discovery');
    check(probe()->autoDiscoverMigrations === false, 'automatic discovery is off by default');
    $compatibility = prepare(probe(['autoDiscoverMigrations' => true]));
    check(count($compatibility->migrationPath) === 2, 'explicit legacy discovery still deduplicates registered sources');

    fixture($root.'/duplicate/migrations', $legacy);
    rejects(['migrationPath' => [$root.'/duplicate/migrations']], 'Duplicate migration');
    rejects(['migrationPath' => [$root.'/modern/migrations']], 'Register namespaced migrations');
    rejects(['migrationPath' => $root.'/missing'], 'Migration directory does not exist');

    // Optional dependency migrations must never be enabled by installation alone.
    fixture($root.'/optional/migrations', $project, 'optional\migrations');
    Yii::$app->extensions[] = ['alias' => ['@optional' => $root.'/optional']];
    $registered = prepare(probe());
    check(count($registered->migrationPath) === 2, 'unregistered optional dependency is not loaded');
    rejects(['autoDiscoverMigrations' => true], 'Register namespaced migrations');

    // Create goes to the project source, not a collected package or runtime dir.
    $create = probe(['useApplicationMigrationConfig' => false]);
    check($create->runAction('create', ['loader_fixture']) === 0, 'native create succeeds');
    check(count(glob($root.'/console/migrations/*loader_fixture.php')) === 1, 'create writes to project migrations');
    $createModern = probe(['migrationPath' => null]);
    check($createModern->runAction('create', ['loader_modern']) === 0, 'native namespaced create succeeds');
    $created = glob($root.'/modern/migrations/*LoaderModern.php');
    check(count($created) === 1 && strpos(file_get_contents($created[0]), 'namespace migrationfixture\\migrations;') !== false, 'namespaced create writes source namespace');
    check(!is_dir($root.'/runtime/db-migrate'), 'create does not create runtime collector');
    echo "OK: {$checks} checks; SQLite memory database only.\n";
} finally {
    if (Yii::$app) { Yii::$app->db->close(); }
    // Only the randomly named fixture directory created by this test.
    FileHelper::removeDirectory($root);
}
