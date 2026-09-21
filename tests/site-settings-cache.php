<?php
// php tests/site-settings-cache.php <vendor/autoload.php>
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
require $argv[1];
require dirname($argv[1]).'/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/src/models/CmsSite.php';
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteAddress;
$app=new yii\console\Application(['id'=>'site-settings-test','basePath'=>__DIR__,'components'=>[
 'cache'=>['class'=>yii\caching\ArrayCache::class],
 'db'=>['class'=>yii\db\Connection::class,'dsn'=>'sqlite::memory:'],
]]);
$db=$app->db;
$db->createCommand('CREATE TABLE cms_site (id INTEGER PRIMARY KEY, name TEXT, is_default INTEGER, image_id INTEGER, logo_light_image_id INTEGER, favicon_storage_file_id INTEGER)')->execute();
$db->createCommand('CREATE TABLE cms_site_address (id INTEGER PRIMARY KEY, cms_site_id INTEGER, priority INTEGER, value TEXT, cms_image_id INTEGER)')->execute();
$db->createCommand()->batchInsert('cms_site',['id','name'],[[1,'one'],[2,'two']])->execute();
$db->createCommand()->batchInsert('cms_site_address',['id','cms_site_id','priority','value'],[[1,1,10,'first'],[2,1,20,'second'],[3,2,10,'other']])->execute();
CmsSite::getTableSchema(); CmsSiteAddress::getTableSchema();
function checkSettings($ok,$message){if(!$ok)throw new RuntimeException($message);}
function measuredSettings(callable $fn,$expected){
 Yii::getLogger()->messages=[]; $result=$fn();$count=0;
 foreach(Yii::getLogger()->messages as $m)if($m[1]===yii\log\Logger::LEVEL_PROFILE_BEGIN && $m[2]==='yii\db\Command::query')++$count;
 checkSettings($count===$expected,"SQL $count, expected $expected");return $result;
}
function siteRead($id,$sql){
 CmsSite::$sites=[]; // Имитируем новый запрос, сохраняя только общий SQL-кеш.
 return measuredSettings(fn()=>CmsSite::getById($id),$sql);
}
function addressRead($id,$sql){
 $site=new CmsSite();$site->id=$id;
 return measuredSettings(fn()=>$site->cmsSiteAddress,$sql);
}
checkSettings(siteRead(1,1)->name==='one','Сайт');
checkSettings(siteRead(1,0)->name==='one','Кеш сайта');
$site=siteRead(2,1);
$one=siteRead(1,0);$one->name='updated';checkSettings($one->save(false),'Сохранение сайта');
checkSettings(siteRead(1,1)->name==='updated','Инвалидация сайта');
checkSettings(addressRead(1,1)->value==='first','Приоритет адресов');
checkSettings(addressRead(1,0)->value==='first','Кеш адреса');
checkSettings(addressRead(2,1)->value==='other','Другой сайт');
yii\caching\TagDependency::invalidate($app->cache,[$one->getCacheTag()]);
checkSettings(siteRead(1,1)->name==='updated','Кнопка: сайт');
checkSettings(addressRead(1,1)->value==='first','Кнопка: адрес');
checkSettings(addressRead(2,0)->value==='other','Кнопка: изоляция');
$address=CmsSiteAddress::findOne(2);$address->priority=1;$address->save(false);
checkSettings(addressRead(1,1)->value==='second','Смена приоритета');
$address->cms_site_id=2;$address->save(false);
checkSettings(addressRead(1,1)->value==='first' && addressRead(2,1)->value==='second','Перенос адреса');
$address->delete();
checkSettings(addressRead(2,1)->value==='other','Удаление');
checkSettings(addressRead(3,1)===null && addressRead(3,0)===null,'Пустой адрес');
$address=new CmsSiteAddress();$address->cms_site_id=3;$address->priority=1;$address->value='new';$address->save(false);
checkSettings(addressRead(3,1)->value==='new','Создание после пустого');
$app->set('otherCache',new yii\caching\ArrayCache());$db->queryCache='otherCache';
siteRead(1,1);siteRead(1,1);addressRead(1,1);addressRead(1,1);
$db->queryCache='cache';
echo "OK: сайт и основной адрес, попадания, события моделей, ручной сброс, приоритеты, переносы, пустые результаты, отдельный queryCache.\n";
