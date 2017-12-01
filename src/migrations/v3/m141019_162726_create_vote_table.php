<?php

use yii\db\Schema;
use yii\db\Migration;

class m141019_162726_create_vote_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_vote}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_vote}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'value'                 => Schema::TYPE_SMALLINT . '(1)',

            'linked_to_model'       => Schema::TYPE_STRING. '(255) NOT NULL', //Коммент обязательно должен быть к кому то привязан
            'linked_to_value'       => Schema::TYPE_STRING. '(255) NOT NULL', //Коммент обязательно должен быть к кому то привязан

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(value);");

        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(linked_to_model);");
        $this->execute("ALTER TABLE {{%cms_vote}} ADD INDEX(linked_to_value);");

        $this->execute("ALTER TABLE {{%cms_vote}} ADD UNIQUE(linked_to_model, linked_to_value, created_by);"); //на одну сущьность пользователь может подписаться 1 раз


        $this->execute("ALTER TABLE {{%cms_vote}} COMMENT = 'Голос, плюс минус';");

        $this->addForeignKey(
            'vote_created_by', "{{%cms_vote}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'vote_updated_by', "{{%cms_vote}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("vote_created_by", "{{%cms_vote}}");
        $this->dropForeignKey("vote_updated_by", "{{%cms_vote}}");

        $this->dropTable("{{%cms_vote}}");
    }
}
