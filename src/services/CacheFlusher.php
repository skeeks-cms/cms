<?php
namespace skeeks\cms\services;

use yii\base\BaseObject;
use yii\caching\CacheInterface;
use yii\caching\ApcCache;

class CacheFlusher extends BaseObject
{
    public function run(?callable $checkpoint = null): array
    {
        $processed = 0;
        $skipped = [];
        $flushed = [];
        foreach (\Yii::$app->getComponents() as $name => $definition) {
            if ($checkpoint) { $checkpoint(['stage' => 'cache', 'processed' => $processed]); }
            $class = is_array($definition) ? ($definition['class'] ?? null) : $definition;
            if ($definition instanceof \Closure) { $class = \Yii::$app->get($name); }
            if (!is_string($class) && !is_object($class)) { continue; }
            if (!is_a($class, CacheInterface::class, true)) { continue; }
            if (PHP_SAPI === 'cli' && is_a($class, ApcCache::class, true)) { $skipped[] = $name; continue; }
            if (!\Yii::$app->get($name)->flush()) {
                throw new \RuntimeException('Cache flush failed: '.$name);
            }
            $flushed[] = $name;
            ++$processed;
            if ($checkpoint) { $checkpoint(['stage' => 'cache', 'processed' => $processed]); }
        }
        return ['processed' => $processed, 'flushed' => $flushed, 'skipped' => $skipped];
    }
}
