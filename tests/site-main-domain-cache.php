<?php
define('YII_ENABLE_ERROR_HANDLER', false);
// Реальные модели и HasTableCache на изолированной SQLite.
require $argv[1];
require dirname($argv[1]).'/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/src/models/CmsSite.php';
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
$app=new yii\console\Application(['id'=>'domain-cache-test','basePath'=>__DIR__,'components'=>[
 'cache'=>['class'=>yii\caching\ArrayCache::class],
 'db'=>['class'=>yii\db\Connection::class,'dsn'=>'sqlite::memory:'],
]]);
$app->set('skeeks', new class extends yii\base\Component { public $siteClass = CmsSite::class; });
$db=$app->db;
$db->createCommand('CREATE TABLE cms_site (id INTEGER PRIMARY KEY)')->execute();
$db->createCommand('CREATE TABLE cms_site_domain (id INTEGER PRIMARY KEY, cms_site_id INTEGER, created_by INTEGER, updated_by INTEGER, created_at INTEGER, updated_at INTEGER, domain TEXT, is_main INTEGER, is_https INTEGER)')->execute();
$db->createCommand()->batchInsert('cms_site',['id'],[[1],[2],[3]])->execute();
$db->createCommand()->batchInsert('cms_site_domain',['id','cms_site_id','domain','is_main','is_https'],[[1,1,'one.test',1,1],[2,2,'two.test',1,0]])->execute();
CmsSite::getTableSchema(); CmsSiteDomain::getTableSchema();
function check($value,$message){if(!$value)throw new RuntimeException($message);}
function readDomain($id,$expectedSql){
 $site=new CmsSite(); $site->id=$id;
 Yii::getLogger()->messages=[];
 $domain=$site->cmsSiteMainDomain;
 $count=0;
 foreach(Yii::getLogger()->messages as $m)if($m[1]===yii\log\Logger::LEVEL_PROFILE_BEGIN && $m[2]==='yii\db\Command::query')++$count;
 check($count===$expectedSql,"site $id: $count SQL, expected $expectedSql");
 return $domain;
}
$ttlSite=new CmsSite(); $ttlSite->id=1;
check($ttlSite->getCmsSiteMainDomain()->queryCacheDuration===28800,'Срок кеша');
check(readDomain(1,1)->domain==='one.test','Первое чтение');
check(readDomain(1,0)->domain==='one.test','Повтор через другой объект');
check(readDomain(2,1)->domain==='two.test','Изоляция сайтов');
// Тот же тег, который инвалидирует кнопка AdminCacheController.
yii\caching\TagDependency::invalidate($app->cache, [$ttlSite->getCacheTag()]);
check(readDomain(1,1)->domain==='one.test','Ручная очистка сайта');
check(readDomain(2,0)->domain==='two.test','Очистка сайта 1 не сбрасывает сайт 2');
$sites=CmsSite::find()->with('cmsSiteMainDomain')->indexBy('id')->all();
check($sites[1]->cmsSiteMainDomain->domain==='one.test' && $sites[2]->cmsSiteMainDomain->domain==='two.test','Eager loading разных сайтов');
check(readDomain(3,1)===null && readDomain(3,0)===null,'Пустая связь кешируется');
$domain=CmsSiteDomain::findOne(1);
$domain->domain='updated.test'; check($domain->save(false),'Сохранение');
check(readDomain(1,1)->domain==='updated.test','Инвалидация обновления');
$domain=new CmsSiteDomain();$domain->cms_site_id=3;$domain->domain='three.test';$domain->is_main=1;$domain->is_https=1;
check($domain->save(false),'Создание');
check(readDomain(3,1)->domain==='three.test','Инвалидация пустого результата');
$newMain=new CmsSiteDomain();$newMain->cms_site_id=1;$newMain->domain='main.test';$newMain->is_main=1;$newMain->is_https=1;
check($newMain->save(false),'Смена основного');
check(readDomain(1,1)->domain==='main.test','Новый основной домен');
check((int)CmsSiteDomain::findOne(1)->is_main===0,'Прежний основной снят');
check(readDomain(1,0)->domain==='main.test','Прогрев');
$newMain->delete();
check(readDomain(1,1)===null,'Инвалидация удаления');
$domain=CmsSiteDomain::findOne(2);
$domain->cms_site_id=1;check($domain->save(false),'Перенос');
check(readDomain(2,1)===null && readDomain(1,1)->domain==='two.test','Оба сайта после переноса');
$site=new CmsSite();$site->id=1;
check($site->getCmsSiteMainDomain()->noCache()->one()->domain==='two.test','Явный noCache');
$app->set('otherCache', new yii\caching\ArrayCache());
$db->queryCache='otherCache'; readDomain(1,1); readDomain(1,1);
echo "OK: основной домен, 8 часов, разные объекты и сайты, пустой результат, создание/обновление/смена/удаление/перенос.\n";
