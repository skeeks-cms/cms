<?php

use yii\db\Schema;
use yii\db\Migration;

class m141019_100557_create_publication_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_publication}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_publication}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING. '(255) NOT NULL',

            'description_short'     => Schema::TYPE_TEXT,
            'description_full'      => Schema::TYPE_TEXT,

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

            'linked_to_model'       => Schema::TYPE_STRING. '(255) NULL', //Коммент обязательно должен быть к кому то привязан
            'linked_to_value'       => Schema::TYPE_STRING. '(255) NULL', //Коммент обязательно должен быть к кому то привязан

            'seo_page_name'         => Schema::TYPE_STRING. '(64) NOT NULL', //обложка

            'count_comment'         => Schema::TYPE_INTEGER . ' NULL', //Количество комментариев

            'count_subscribe'       => Schema::TYPE_INTEGER . ' NULL', //Количество подписчиков
            'users_subscribers'     => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые подписались (их id через запятую)


            'count_vote'            => Schema::TYPE_INTEGER . ' NULL', //Количество голосов
            'result_vote'           => Schema::TYPE_INTEGER . ' NULL', //Результат голосования
            'users_votes_up'        => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали +
            'users_votes_down'      => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали -

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10', //статус, активна некативна, удалено
            'status_adult'          => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 0', //Возрастной статус 0 - не проверено, 1-для всех, 2-типо эротические материалы, 3-порно

            'page_options'          => Schema::TYPE_TEXT. ' NULL', //

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD UNIQUE(seo_page_name);");

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(count_comment);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(count_subscribe);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(linked_to_model);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(linked_to_value);");

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(status);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(status_adult);");

        $this->execute("ALTER TABLE {{%cms_publication}} COMMENT = 'Публикация';");


        $this->addForeignKey(
            'publication_created_by', "{{%cms_publication}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'publication_updated_by', "{{%cms_publication}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("publication_created_by", "{{%cms_publication}}");
        $this->dropForeignKey("publication_updated_by", "{{%cms_publication}}");

        $this->dropTable("{{%cms_publication}}");
    }
}
