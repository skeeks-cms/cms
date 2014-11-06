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
        $tableExist = $this->db->getTableSchema("{{%user_group}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user_group}}', [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'groupname'             => Schema::TYPE_STRING . ' NOT NULL',
            'description'           => Schema::TYPE_STRING . '(32) NOT NULL',

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10',

            'image'                 => Schema::TYPE_TEXT. ' NULL', //главное изображение
            'image_cover'           => Schema::TYPE_TEXT. ' NULL', //обложка
            'images'                => Schema::TYPE_TEXT. ' NULL', //
            'files'                 => Schema::TYPE_TEXT. ' NULL', //

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%user_group}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%user_group}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%user_group}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%user_group}} ADD INDEX(updated_by);");

        $this->execute("ALTER TABLE {{%user_group}} ADD UNIQUE(groupname);");

        $this->execute("ALTER TABLE {{%user_group}} COMMENT = 'Группа пользователя';");

        $this->addForeignKey(
            'user_group_created_by', "{{%user_group}}",
            'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'user_group_updated_by', "{{%user_group}}",
            'updated_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT'
        );



        $this->insert('{{%user_group}}', [
            "groupname"             => "root",
            "description"           => "Суперпользователи",
            "status"                => 10,
        ]);

        $this->insert('{{%user_group}}', [
            "groupname"             => "admin",
            "description"           => "Администраторы",
            "status"                => 10,
        ]);

        $this->insert('{{%user_group}}', [
            "groupname"             => "manager",
            "description"           => "Менеджер",
            "status"                => 10,
        ]);

        $this->insert('{{%user_group}}', [
            "groupname"             => "user",
            "description"           => "Пользователь",
            "status"                => 10,
        ]);


        $this->execute("ALTER TABLE {{%user}} ADD `group_id` INT(11) NULL;");

        $this->addForeignKey(
            'user_user_group_updated_by', "{{%user}}",
            'group_id', '{{%user_group}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("user_user_group_updated_by", "{{%user}}");
        $this->dropForeignKey("user_group_created_by", "{{%user_group}}");
        $this->dropForeignKey("user_group_updated_by", "{{%user_group}}");

        $this->dropColumn('{{%user}}', 'group_id');
        $this->dropTable('{{%user_group}}');
    }
}
