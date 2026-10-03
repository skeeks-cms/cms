<?php
// Real lead query/model against disposable SQLite; no application DB writes.
namespace {
    define('YII_ENABLE_ERROR_HANDLER', false);
    require $argv[1];
    require dirname($argv[1]).'/yiisoft/yii2/Yii.php';
}
namespace skeeks\cms\models {
    class Core extends \yii\db\ActiveRecord {}
    class User extends Core {
        public $subordinates = [];
        public static function tableName() { return '{{%cms_user}}'; }
    }
    class CmsUser extends User {
        public static function find() { return new \skeeks\cms\models\queries\CmsUserQuery(static::class); }
    }
    class CmsCompany extends Core {
        public static function tableName() { return '{{%cms_company}}'; }
        public static function find() { return new \skeeks\cms\models\queries\CmsCompanyQuery(static::class); }
        public function getManagers() {
            return $this->hasMany(CmsUser::class, ['id'=>'cms_user_id'])
                ->viaTable('{{%cms_company2manager}}', ['cms_company_id'=>'id']);
        }
    }
}
namespace {
    $app = new \yii\console\Application(['id'=>'lead-access-test','basePath'=>__DIR__, 'components'=>[
        'db'=>['class'=>\yii\db\Connection::class,'dsn'=>'sqlite::memory:'],
        'authManager'=>new class extends \yii\base\Component {
            public function checkAccess($id, $permission) {
                return $permission === 'cms/admin-lead' ? !in_array((int)$id, [112], true) : (int)$id === 1;
            }
        },
    ]]);
    $db=$app->db;
    foreach ([
        'cms_user'=>'id INTEGER PRIMARY KEY, is_worker INTEGER, is_active INTEGER, cms_site_id INTEGER',
        'cms_company'=>'id INTEGER PRIMARY KEY',
        'cms_company2manager'=>'cms_company_id INTEGER, cms_user_id INTEGER',
        'cms_company2user'=>'cms_company_id INTEGER, cms_user_id INTEGER',
        'cms_lead'=>'id INTEGER PRIMARY KEY, status TEXT, source_type TEXT, executor_id INTEGER, submitted_by_id INTEGER, partner_id INTEGER, cms_user_id INTEGER, cms_company_id INTEGER, cms_site_id INTEGER',
        'cms_lead_phone'=>'cms_lead_id INTEGER, value TEXT',
        'cms_lead_email'=>'cms_lead_id INTEGER, value TEXT',
        'cms_company_phone'=>'cms_company_id INTEGER, value TEXT',
        'cms_company_email'=>'cms_company_id INTEGER, value TEXT',
        'cms_user_phone'=>'cms_user_id INTEGER, value TEXT',
        'cms_user_email'=>'cms_user_id INTEGER, value TEXT',
    ] as $table=>$columns) { $db->createCommand("CREATE TABLE $table ($columns)")->execute(); }
    $db->createCommand()->batchInsert('cms_user',['id','is_worker','is_active','cms_site_id'],[[1,1,1,6],[110,1,1,6],[111,1,1,6],[112,1,1,6],[113,1,0,6],[500,0,1,6]])->execute();
    $db->createCommand()->batchInsert('cms_company',['id'],[[10],[20]])->execute();
    $db->createCommand()->batchInsert('cms_company2manager',['cms_company_id','cms_user_id'],[[10,110],[10,112],[10,113],[20,111]])->execute();
    $db->createCommand()->insert('cms_company2user',['cms_company_id'=>10,'cms_user_id'=>500])->execute();
    foreach ([1=>[],2=>['cms_company_id'=>10],3=>['cms_company_id'=>20],4=>['cms_user_id'=>500],5=>[],6=>[],7=>[],8=>['executor_id'=>110],9=>[],10=>[],11=>[]] as $id=>$attrs) {
        $db->createCommand()->insert('cms_lead',array_merge(['id'=>$id,'status'=>'new','source_type'=>'form','cms_site_id'=>6],$attrs))->execute();
    }
    $db->createCommand()->batchInsert('cms_company_phone',['cms_company_id','value'],[[10,'+7 (900) 123-45-67'],[20,'+7 901 123-45-67']])->execute();
    $db->createCommand()->batchInsert('cms_lead_phone',['cms_lead_id','value'],[[5,'89001234567'],[9,'1234567'],[11,'79011234567']])->execute();
    $db->createCommand()->insert('cms_company_email',['cms_company_id'=>10,'value'=>'Hello@Example.test'])->execute();
    $db->createCommand()->insert('cms_lead_email',['cms_lead_id'=>6,'value'=>' hello@example.test '])->execute();
    $db->createCommand()->insert('cms_user_email',['cms_user_id'=>500,'value'=>'client@example.test'])->execute();
    $db->createCommand()->insert('cms_lead_email',['cms_lead_id'=>7,'value'=>'client@example.test'])->execute();
    $worker=\skeeks\cms\models\CmsUser::findOne(110);
    $ids=\skeeks\cms\models\CmsLead::find()->forManager($worker)->orderBy('id')->select('cms_lead.id')->column();
    if (array_map('intval',$ids)!==[2,4,5,6,7,8]) { throw new \RuntimeException('Visibility: '.json_encode($ids)); }
    foreach ([1=>[1],2=>[1,110],3=>[1,111],5=>[1,110],7=>[1,110],9=>[1],11=>[1,111]] as $id=>$expected) {
        $actual=\skeeks\cms\models\CmsLead::findOne($id)->availableManagerIds(); sort($actual);
        if ($actual!==$expected) { throw new \RuntimeException("Recipients $id: ".json_encode($actual)); }
    }
    $boss=\skeeks\cms\models\CmsUser::findOne(111); $boss->subordinates=[$worker];
    if (!\skeeks\cms\models\CmsLead::find()->forManager($boss)->andWhere(['cms_lead.id'=>2])->exists()) { throw new \RuntimeException('Company hierarchy'); }
    if (\skeeks\cms\models\CmsLead::find()->forManager($worker)->andWhere(['cms_lead.id'=>1])->exists()) { throw new \RuntimeException('Anonymous direct ID'); }
    if (\skeeks\cms\models\CmsLead::find()->forManager(\skeeks\cms\models\CmsUser::findOne(1))->count()!=11) { throw new \RuntimeException('Admin triage'); }
    echo "OK: anonymous/private leads, explicit company/client links, normalized contacts, short-phone rejection, executor/hierarchy, active permitted recipients and admin triage\n";
}
