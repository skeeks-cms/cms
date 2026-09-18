<?php
// Isolated schema test: no site database is used.
$vendor=getenv('TEST_VENDOR')?:'/deps';
require $vendor.'/autoload.php';require $vendor.'/yiisoft/yii2/Yii.php';
new yii\console\Application(['id'=>'tree-sync-test','basePath'=>__DIR__,'vendorPath'=>$vendor,'extensions'=>[], 'components'=>['db'=>['class'=>yii\db\Connection::class,'dsn'=>'sqlite::memory:']]]);
require dirname(__DIR__).'/src/migrations/m260917_210000__tree_gpd_sync_flag.php';
$db=Yii::$app->db;$checks=0;
foreach(['','prefix_'] as $prefix){
 $db->tablePrefix=$prefix;$db->schema->refresh();
 $db->createCommand()->createTable('{{%cms_tree}}',['id'=>'pk','sx_id'=>'integer','name'=>'text'])->execute();
 $db->createCommand()->batchInsert('{{%cms_tree}}',['id','sx_id','name'],[[1,12,'Local edit'],[2,null,'Local page'],[3,0,'Unlinked']])->execute();
 (new m260917_210000__tree_gpd_sync_flag())->safeUp();
 $flags=$db->createCommand('SELECT id,is_sx_info_update FROM {{%cms_tree}}')->queryAll();
 if(array_column($flags,'is_sx_info_update','id')!=[1=>0,2=>1,3=>1])throw new RuntimeException('Wrong existing defaults');++$checks;
 $db->createCommand()->insert('{{%cms_tree}}',['sx_id'=>13,'name'=>'New category'])->execute();
 if((int)$db->createCommand('SELECT is_sx_info_update FROM {{%cms_tree}} WHERE sx_id=13')->queryScalar()!==1)throw new RuntimeException('Wrong new default');++$checks;
 if($db->createCommand('SELECT name FROM {{%cms_tree}} WHERE id=1')->queryScalar()!=='Local edit')throw new RuntimeException('Data changed');++$checks;
}
echo "PASS $checks migration checks\n";