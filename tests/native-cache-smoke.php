<?php
define('YII_ENABLE_ERROR_HANDLER',false);
$vendor=(getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor';
require $vendor.'/autoload.php';require $vendor.'/yiisoft/yii2/Yii.php';
$app=new yii\console\Application(['id'=>'cache-test','basePath'=>__DIR__,'vendorPath'=>$vendor,'extensions'=>[],
'components'=>['cache'=>['class'=>yii\caching\ArrayCache::class],
 'extraCache'=>static function(){return new yii\caching\ArrayCache();}]]);
$check=static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$app->cache->set('x',1);$app->extraCache->set('x',1);
$beats=0;$service=new skeeks\cms\services\CacheFlusher();
$result=$service->run(static function($p)use(&$beats){++$beats;});
$check($result['processed']===2 && !$app->cache->get('x') && !$app->extraCache->get('x'),'Configured and closure caches flushed');
$check($beats>2,'Cache checkpoints');
$app->cache->set('x',1);
try{$service->run(static function(){throw new RuntimeException('cancel');});}
catch(RuntimeException $e){$check($app->cache->get('x')===1,'Cancellation preserves cache');}
$app->set('badCache',new class extends yii\caching\ArrayCache{public function flush(){return false;}});
try{$service->run();throw new LogicException('Failure swallowed');}
catch(RuntimeException $e){$check(strpos($e->getMessage(),'badCache')!==false,'Flush failure propagates');}
echo "PASS: 4 cache checks\n";
