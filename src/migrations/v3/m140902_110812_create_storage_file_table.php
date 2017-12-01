<?php

use yii\db\Schema;
use yii\db\Migration;

class m140902_110812_create_storage_file_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_storage_file}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_storage_file}}", [
            'id'                    => Schema::TYPE_PK,

            'src'                   => Schema::TYPE_STRING. '(255) NOT NULL',

            'cluster_id'            => Schema::TYPE_STRING. '(16) NULL',
            'cluster_file'           => Schema::TYPE_STRING. '(255) NULL',

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'size'                  => Schema::TYPE_BIGINT. '(32)',
            'type'                  => Schema::TYPE_STRING. '(16)',
            'mime_type'             => Schema::TYPE_STRING. '(16)',
            'extension'             => Schema::TYPE_STRING. '(16)',

            'original_name'         => Schema::TYPE_STRING. '(255)', //оригинальное название файла
            'name_to_save'          => Schema::TYPE_STRING. '(32)',  //оригинальное название файла

            'name'                  => Schema::TYPE_STRING. '(255)', //название для отображения на странице

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10', //статус, активна некативна, удалено
            'status_adult'          => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 0', //Возрастной статус 0 - не проверено, 1-для всех, 2-типо эротические материалы, 3-порно

            'description_short'     => Schema::TYPE_TEXT,
            'description_full'      => Schema::TYPE_TEXT,

            //Если файл картинка, заполняем дополнительные сведения
            'image_height'          => Schema::TYPE_INTEGER . ' NULL',
            'image_width'           => Schema::TYPE_INTEGER . ' NULL',


            'count_comment'         => Schema::TYPE_INTEGER . ' NULL', //Количество комментариев

            'count_subscribe'       => Schema::TYPE_INTEGER . ' NULL', //Количество подписчиков
            'users_subscribers'     => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые подписались (их id через запятую)

            'count_vote'            => Schema::TYPE_INTEGER . ' NULL', //Количество голосов
            'result_vote'           => Schema::TYPE_INTEGER . ' NULL', //Результат голосования
            'users_votes_up'        => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали +
            'users_votes_down'      => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали -


            'linked_to_model'       => Schema::TYPE_STRING. '(255) NULL', //Коммент обязательно должен быть к кому то привязан
            'linked_to_value'       => Schema::TYPE_STRING. '(255) NULL', //Коммент обязательно должен быть к кому то привязан

            'page_options'          => Schema::TYPE_TEXT. ' NULL', //

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD UNIQUE(src);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD UNIQUE(cluster_id, cluster_file);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(cluster_id);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(cluster_file);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(size);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(extension);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(status);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(status_adult);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(name_to_save);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(type);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(mime_type);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(image_height);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(image_width);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(count_comment);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(count_subscribe);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(linked_to_model);");
        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(linked_to_value);");

        $this->execute("ALTER TABLE {{%cms_storage_file}} COMMENT = 'Файл';");

        $this->addForeignKey(
            'storage_file_created_by', "{{%cms_storage_file}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'storage_file_updated_by', "{{%cms_storage_file}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("storage_file_created_by", "{{%cms_storage_file}}");
        $this->dropForeignKey("storage_file_updated_by", "{{%cms_storage_file}}");

        $this->dropTable("{{%cms_storage_file}}");
    }
}
