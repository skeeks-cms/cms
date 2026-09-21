<?php
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER',false);
require $argv[1];require dirname($argv[1]).'/yiisoft/yii2/Yii.php';
class RouteTreeFixture extends yii\db\ActiveRecord {
 public static function tableName(){return 'route_tree';}
 public function getTableCacheTagCmsSite(){return 'test-tree';}
 public function getSite(){return null;}
}
class_alias(RouteTreeFixture::class,'skeeks\cms\models\CmsTree');
require dirname(__DIR__).'/src/components/urlRules/UrlRuleTree.php';
$appClass=($argv[2]??'web')==='console' ? yii\console\Application::class : yii\web\Application::class;
$app=new $appClass(['id'=>'route-test','basePath'=>__DIR__,'components'=>[
 'db'=>['class'=>yii\db\Connection::class,'dsn'=>'sqlite::memory:'],
 'cache'=>['class'=>yii\caching\ArrayCache::class],
]]);
$app->set('skeeks',new class extends yii\base\Component {public $site;public function init(){$this->site=(object)['id'=>1];}});
$app->set('cms',new class extends yii\base\Component {public $currentTree;public function setCurrentTree($tree){$this->currentTree=$tree;}});
$app->db->createCommand('CREATE TABLE route_tree (id INTEGER PRIMARY KEY, cms_site_id INTEGER, level INTEGER, dir TEXT, redirect TEXT, redirect_tree_id INTEGER)')->execute();
$app->db->createCommand()->batchInsert('route_tree',['id','cms_site_id','level','dir'],[[1,1,0,''],[2,1,1,'catalog']])->execute();
RouteTreeFixture::getTableSchema();
$manager=new yii\web\UrlManager();
$rule=new skeeks\cms\components\urlRules\UrlRuleTree();
foreach([''=>1,'catalog'=>2] as $path=>$id){
 $request=new yii\web\Request(['cookieValidationKey'=>'test']);$request->setPathInfo($path);$request->setQueryParams([]);
 $route=$rule->parseRequest($manager,$request);
 if($route!==['cms/tree/view',['id'=>$id]])throw new RuntimeException('Маршрут изменился');
 if($rule::$models)throw new RuntimeException('Разбор URL наполнил статический массив');
 Yii::getLogger()->messages=[];
 $url=$rule->createUrl($manager,'cms/tree/view',['id'=>$id]);
 if($url!==$path)throw new RuntimeException('URL изменился');
 $sql=0;
 foreach(Yii::getLogger()->messages as $m)if($m[1]===yii\log\Logger::LEVEL_PROFILE_BEGIN && $m[2]==='yii\db\Command::query')++$sql;
 $expected=$app instanceof yii\web\Application ? 0 : 1;
 if($sql!==$expected)throw new RuntimeException('Неверное число SQL для web/console');
 $rule::$models=[];
}
if($app instanceof yii\web\Application){
 for($id=10;$id<1010;++$id){
  $tree=new RouteTreeFixture();$tree->id=$id;$tree->dir='page-'.$id;
  $app->cms->currentTree=$tree;
  $rule->createUrl($manager,'cms/tree/view',['id'=>$id]);
 }
 if($rule::$models)throw new RuntimeException('Текущие разделы накапливаются');
}
echo "OK: web без повторного SQL и накопления 1000 моделей; консоль сохраняет прежний путь.\n";
