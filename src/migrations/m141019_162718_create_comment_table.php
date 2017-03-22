<?php

use yii\db\Schema;
use yii\db\Migration;

class m141019_162718_create_comment_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_comment}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_comment}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'content'               => Schema::TYPE_TEXT,

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

            'linked_to_model'       => Schema::TYPE_STRING. '(255) NOT NULL', //Коммент обязательно должен быть к кому то привязан
            'linked_to_value'       => Schema::TYPE_STRING. '(255) NOT NULL', //Коммент обязательно должен быть к кому то привязан

            'count_subscribe'       => Schema::TYPE_INTEGER . ' NULL',
            'users_subscribers'     => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые подписались (их id через запятую)

            'count_vote'            => Schema::TYPE_INTEGER . ' NULL', //Количество голосов
            'result_vote'           => Schema::TYPE_INTEGER . ' NULL', //Результат голосования
            'users_votes_up'        => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали +
            'users_votes_down'      => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали -

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10', //статус, активна некативна, удалено
            'status_adult'          => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 0', //Возрастной статус 0 - не проверено, 1-для всех, 2-типо эротические материалы, 3-порно

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(linked_to_model);");
        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(linked_to_value);");


        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(count_subscribe);");
        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(status);");
        $this->execute("ALTER TABLE {{%cms_comment}} ADD INDEX(status_adult);");

        $this->execute("ALTER TABLE {{%cms_comment}} COMMENT = 'Комментарий к игре';");

        $this->addForeignKey(
            'comment_created_by', "{{%cms_comment}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'comment_updated_by', "{{%cms_comment}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("comment_created_by", "{{%cms_comment}}");
        $this->dropForeignKey("comment_updated_by", "{{%cms_comment}}");

        $this->dropTable("{{%cms_comment}}");
    }
}
