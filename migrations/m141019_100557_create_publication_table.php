<?php

use yii\db\Schema;
use yii\db\Migration;

class m141019_100557_create_publication_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%publication}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%publication}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING. '(255) NOT NULL',

            'description_short'     => Schema::TYPE_TEXT,
            'description_full'      => Schema::TYPE_TEXT,

            'meta_title'            => Schema::TYPE_STRING . '(255)',
            'meta_description'      => Schema::TYPE_TEXT,
            'meta_keywords'         => Schema::TYPE_TEXT,

            'image'                 => Schema::TYPE_TEXT. ' NULL', //главное изображение
            'image_cover'           => Schema::TYPE_TEXT. ' NULL', //обложка
            'images'                => Schema::TYPE_TEXT. ' NULL', //
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

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%publication}} ADD UNIQUE(seo_page_name);");

        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(count_comment);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(count_subscribe);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(linked_to_model);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(linked_to_value);");

        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(status);");
        $this->execute("ALTER TABLE {{%publication}} ADD INDEX(status_adult);");

        $this->execute("ALTER TABLE {{%publication}} COMMENT = 'Публикация';");


        $this->addForeignKey(
            'publication_created_by', "{{%publication}}",
            'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'publication_updated_by', "{{%publication}}",
            'updated_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("publication_created_by", "{{%publication}}");
        $this->dropForeignKey("publication_updated_by", "{{%publication}}");

        $this->dropTable("{{%publication}}");
    }
}
