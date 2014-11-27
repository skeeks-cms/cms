<?php
/**
 * m140801_201442_create_user_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m141106_100557_create_user_group_table
 */
class m141106_100557_create_user_group_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_user_group}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cms_user_group}}', [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'groupname'             => Schema::TYPE_STRING . ' NOT NULL',
            'description'           => Schema::TYPE_STRING . '(32) NOT NULL',

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10',

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_user_group}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_user_group}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_user_group}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%cms_user_group}} ADD INDEX(updated_by);");

        $this->execute("ALTER TABLE {{%cms_user_group}} ADD UNIQUE(groupname);");

        $this->execute("ALTER TABLE {{%cms_user_group}} COMMENT = 'Группа пользователя';");

        $this->addForeignKey(
            'user_group_created_by', "{{%cms_user_group}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'user_group_updated_by', "{{%cms_user_group}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );



        $this->insert('{{%cms_user_group}}', [
            "groupname"             => "root",
            "description"           => "Суперпользователи",
            "status"                => 10,
        ]);

        $this->insert('{{%cms_user_group}}', [
            "groupname"             => "admin",
            "description"           => "Администраторы",
            "status"                => 10,
        ]);

        $this->insert('{{%cms_user_group}}', [
            "groupname"             => "manager",
            "description"           => "Менеджер",
            "status"                => 10,
        ]);

        $this->insert('{{%cms_user_group}}', [
            "groupname"             => "user",
            "description"           => "Пользователь",
            "status"                => 10,
        ]);


        $this->execute("ALTER TABLE {{%cms_user}} ADD `group_id` INT(11) NULL;");

        $this->addForeignKey(
            'user_user_group_updated_by', "{{%cms_user}}",
            'group_id', '{{%cms_user_group}}', 'id', 'RESTRICT', 'RESTRICT'
        );


        $this->insert('{{%cms_user}}', [
            "username"              => "root",
            "name"                  => "Семенов Александр",
            "city"                  => "Зеленоград",
            "address"               => "Зеленоград, ул. Каменка, 2004-25",
            "auth_key"              => "otv60YW-nV6-8GRI4La3vYNhu_-dmp_n",
            "password_hash"         => '$2y$13$tEMlbu9DvkU3RDCg.sZwM.JNScy.HJXFqG.Ew.n5fwcdAPxHsFdla',
            "password_reset_token"  => 'wn49wJLj9OMVjgj8bBzBjND4nFixyJgt_1413297645',
            "email"                 => 'admin@skeeks.com',
            "role"                  => 10,
            "status"                => 10,
            "group_id"              => 1,
        ]);
    }

    public function down()
    {
        $this->dropForeignKey("user_user_group_updated_by", "{{%cms_user}}");
        $this->dropForeignKey("user_group_created_by", "{{%cms_user_group}}");
        $this->dropForeignKey("user_group_updated_by", "{{%cms_user_group}}");

        $this->dropColumn('{{%cms_user}}', 'group_id');
        $this->dropTable('{{%cms_user_group}}');
    }
}
