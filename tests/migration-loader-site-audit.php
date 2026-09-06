<?php
/**
 * Read-only compatibility audit with the consuming project's console config.
 * Reads existing migration history; does not create history or apply migrations.
 */
define('ROOT_DIR', getenv('SKEEKS_APP_ROOT') ?: '/app');
define('YII_ENV', 'dev');
define('YII_DEBUG', true);
require ROOT_DIR.'/vendor/skeeks/cms/bootstrap.php';

$config = new \Yiisoft\Config\Config(
    new \Yiisoft\Config\ConfigPaths(ROOT_DIR, 'config'), null,
    [\Yiisoft\Config\Modifier\RecursiveMerge::groups('console', 'console-'.ENV, 'params', 'params-console-'.ENV)],
    'params-console-'.ENV
);
$data = $config->has('console-'.ENV) ? $config->get('console-'.ENV) : $config->get('console');
$app = new yii\console\Application($data);

class SiteMigrationAudit extends \skeeks\cms\console\controllers\MigrateController
{
    public $emptyHistory = false;
    protected function getMigrationHistory($limit)
    {
        if ($this->emptyHistory) { return []; }
        // Deliberately bypass Yii's automatic history-table creation.
        if ($this->db->schema->getTableSchema($this->migrationTable) === null) {
            return [];
        }
        $rows = (new yii\db\Query())->from($this->migrationTable)->all($this->db);
        return yii\helpers\ArrayHelper::map($rows, 'version', 'apply_time');
    }
    public function pending() { return $this->getNewMigrations(); }
    public function history() { return $this->getMigrationHistory(null); }
}

$cms = $app->getModule('cms');
$settings = $cms->controllerMap['migrate'] ?? [];
unset($settings['class']);
if (in_array('--explicit', $argv, true)) { $settings['autoDiscoverMigrations'] = false; }
$runner = new SiteMigrationAudit('migrate', $cms, $settings);
$runner->beforeAction($runner->createAction('new'));
$before = $runner->history();
$pending = $runner->pending();
$legacy = [];
$directories = [];
foreach ($app->extensions as $extension) {
    foreach ($extension['alias'] ?? [] as $path) {
        $directories[] = $path.'/migrations';
    }
}
$directories[] = Yii::getAlias('@console/migrations');
foreach ($directories as $directory) {
    foreach (glob($directory.'/*.php') ?: [] as $file) {
        $name = basename($file, '.php');
        if (!preg_match('/^m\d{6}_?\d{6}\D/i', $name)) { continue; }
        // Historical baseline for this project's cms-job transport: the native
        // yii2-queue chain was never enabled. This is not loader configuration.
        $nativeQueue = Yii::getAlias('@yii/queue/drivers/db/migrations', false);
        if ($nativeQueue && realpath($directory) === realpath($nativeQueue)) { continue; }
        $legacy[$name] = true;
    }
}
$oldPending = array_diff(array_keys($legacy), array_keys($before));
$added = array_values(array_diff($pending, $oldPending));
$removed = array_values(array_diff($oldPending, $pending));
$runner->emptyHistory = true;
$nativeInventory = $runner->pending();
$runner->emptyHistory = false;
$inventoryAdded = array_values(array_diff($nativeInventory, array_keys($legacy)));
$inventoryRemoved = array_values(array_diff(array_keys($legacy), $nativeInventory));
echo json_encode([
    'autoDiscoverMigrations' => $runner->autoDiscoverMigrations,
    'sourcePaths' => count($runner->migrationPath),
    'namespaces' => $runner->migrationNamespaces,
    'historyCount' => count($before),
    'legacyPendingCount' => count($oldPending),
    'nativePendingCount' => count($pending),
    'added' => $added, 'removed' => $removed,
    'legacyInventoryCount' => count($legacy),
    'nativeInventoryCount' => count($nativeInventory),
    'inventoryAdded' => $inventoryAdded, 'inventoryRemoved' => $inventoryRemoved,
    'historyUnchanged' => $before === $runner->history(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
exit($added || $removed || $inventoryAdded || $inventoryRemoved || $before !== $runner->history() ? 1 : 0);
