<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\console\controllers;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Yii migration runner with compatibility discovery for older CMS packages.
 * Files stay at their original paths; migration history is owned by Yii.
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    /** @var string|array|null Project migrations come first, also for create. */
    public $migrationPath = ['@console/migrations'];

    /** @var bool Discover <extension alias>/migrations for legacy packages. */
    public $autoDiscoverMigrations = false;

    /** @var bool Read package registrations from the application's migrate config. */
    public $useApplicationMigrationConfig = true;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['autoDiscoverMigrations', 'useApplicationMigrationConfig']);
    }

    public function beforeAction($action)
    {
        if ($action->id !== 'create' && is_string($this->migrationPath) && !is_dir(Yii::getAlias($this->migrationPath))) {
            throw new InvalidConfigException("Migration directory does not exist: {$this->migrationPath}");
        }
        $selected = array_intersect(['migrationPath', 'migrationNamespaces'], $this->getPassedOptions());
        // Do not instantiate the other controller: its config may point here.
        // An explicit CLI selection must not unexpectedly include every package.
        $config = Yii::$app->controllerMap['migrate'] ?? [];
        if (!$selected && $this->useApplicationMigrationConfig && $this->module !== Yii::$app && is_array($config)) {
            if ($this->migrationPath !== null) {
                $this->migrationPath = array_merge((array) $this->migrationPath, (array) ($config['migrationPath'] ?? []));
            }
            $this->migrationNamespaces = array_merge((array) $this->migrationNamespaces, (array) ($config['migrationNamespaces'] ?? []));
        }

        // Creation uses the configured destination, never a discovered vendor path.
        if ($action->id !== 'create') {
            $paths = (array) $this->migrationPath;
            $namespaces = [];
            foreach ((array) $this->migrationNamespaces as $namespace) {
                $namespace = trim($namespace, '\\');
                $namespaces[$namespace] = $this->canonicalPath('@'.str_replace('\\', '/', $namespace));
            }
            $this->migrationNamespaces = array_keys($namespaces);

            if ($this->autoDiscoverMigrations && !$selected && $this->migrationPath !== null) {
                foreach (Yii::$app->extensions as $extension) {
                    foreach ($extension['alias'] ?? [] as $path) {
                        $path = $this->canonicalPath($path.'/migrations');
                        if (is_dir($path) && !in_array($path, $namespaces, true)) {
                            $paths[] = $path;
                        }
                    }
                }
            }

            $resolved = [];
            foreach ($paths as $path) {
                $resolved[] = $this->canonicalPath($path);
            }
            $this->migrationPath = $this->migrationPath === null ? null : array_values(array_unique($resolved));
            $this->validateMigrationSources($namespaces);
        }

        return parent::beforeAction($action);
    }

    private function canonicalPath($path)
    {
        $path = Yii::getAlias($path);
        $path = realpath($path) ?: $path;
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /** Fail before any SQL rather than silently selecting an ambiguous class. */
    private function validateMigrationSources(array $namespaces)
    {
        $sources = [];
        foreach ((array) $this->migrationPath as $path) {
            $sources[] = [$path, ''];
        }
        foreach ($namespaces as $namespace => $path) {
            $sources[] = [$path, $namespace];
        }
        $classes = [];
        foreach ($sources as [$path, $namespace]) {
            // Yii permits missing array paths (e.g. a new site's project migrations).
            if (!is_dir($path)) {
                if ($namespace !== '') {
                    throw new InvalidConfigException("Migration namespace '{$namespace}' directory does not exist: {$path}");
                }
                continue;
            }
            foreach (scandir($path) as $file) {
                if (!preg_match('/^(m\\d{6}_?\\d{6}\\D.*?)\\.php$/is', $file, $match) || !is_file($path.'/'.$file)) {
                    continue;
                }
                $source = $path.'/'.$file;
                $declared = $this->readNamespace($source);
                if ($declared !== $namespace) {
                    throw new InvalidConfigException("Migration {$source} declares namespace '{$declared}', expected '{$namespace}'. "
                        .'Register namespaced migrations in migrationNamespaces, not migrationPath; '
                        .'disable autoDiscoverMigrations and register required sources explicitly.');
                }
                $class = ltrim($namespace.'\\'.$match[1], '\\');
                $key = strtolower($class); // PHP class names are case-insensitive.
                if (isset($classes[$key]) && $classes[$key] !== $source) {
                    throw new InvalidConfigException("Duplicate migration {$class}: {$classes[$key]} and {$source}. "
                        .'Select one source explicitly; do not rename an already applied migration.');
                }
                $classes[$key] = $source;
            }
        }
    }

    /** Tokenize without including the file or executing migration code. */
    private function readNamespace($file)
    {
        $tokens = token_get_all(file_get_contents($file));
        foreach ($tokens as $i => $token) {
            if (!is_array($token) || $token[0] !== T_NAMESPACE) {
                continue;
            }
            // PHP 7 tokenizes namespace\function() as T_NAMESPACE + separator.
            $next = $tokens[$i + 1] ?? null;
            if (is_array($next) && $next[0] === T_NS_SEPARATOR) {
                continue;
            }
            $namespace = '';
            for ($j = $i + 1, $count = count($tokens); $j < $count; $j++) {
                $part = $tokens[$j];
                if ($part === ';' || $part === '{') {
                    return trim($namespace, '\\');
                }
                if (is_array($part) && !in_array($part[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $namespace .= $part[1];
                }
            }
        }
        return '';
    }
}
