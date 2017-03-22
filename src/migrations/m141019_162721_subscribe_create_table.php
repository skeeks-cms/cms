<?php
/**
 * m141019_162721_subscribe_create_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

class m141019_162721_subscribe_create_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_subscribe}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_subscribe}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'linked_to_model'       => Schema::TYPE_STRING. '(255) NOT NULL', //Коммент обязательно должен быть к кому то привязан
            'linked_to_value'       => Schema::TYPE_STRING. '(255) NOT NULL', //Коммент обязательно должен быть к кому то привязан

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD INDEX(linked_to_model);");
        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD INDEX(linked_to_value);");

        $this->execute("ALTER TABLE {{%cms_subscribe}} ADD UNIQUE(linked_to_model, linked_to_value, created_by);"); //на одну сущьность пользователь может подписаться 1 раз

        $this->execute("ALTER TABLE {{%cms_subscribe}} COMMENT = 'Подписка или избранное';");

        $this->addForeignKey(
            'subscribe_created_by', "{{%cms_subscribe}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'subscribe_updated_by', "{{%cms_subscribe}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("subscribe_created_by", "{{%cms_subscribe}}");
        $this->dropForeignKey("subscribe_updated_by", "{{%cms_subscribe}}");

        $this->dropTable("{{%cms_subscribe}}");
    }
}
