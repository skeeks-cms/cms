<?php

use yii\db\Schema;
use yii\db\Migration;

class m141104_100557_create_cms_tree_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_tree}}", true);
        if ($tableExist)
        {
            $this->dropTable("{{%cms_tree}}");
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_tree}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING. '(255) NOT NULL',

            'description_short'     => Schema::TYPE_TEXT,
            'description_full'      => Schema::TYPE_TEXT,

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

            'page_options'          => Schema::TYPE_TEXT. ' NULL', //

            'seo_page_name'         => Schema::TYPE_STRING. '(64) NULL', //обложка

            'count_comment'         => Schema::TYPE_INTEGER . ' NULL', //Количество комментариев

            'count_subscribe'       => Schema::TYPE_INTEGER . ' NULL', //Количество подписчиков
            'users_subscribers'     => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые подписались (их id через запятую)

            'count_vote'            => Schema::TYPE_INTEGER . ' NULL', //Количество голосов
            'result_vote'           => Schema::TYPE_INTEGER . ' NULL', //Результат голосования
            'users_votes_up'        => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали +
            'users_votes_down'      => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали -

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10', //статус, активна некативна, удалено
            'status_adult'          => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 0', //Возрастной статус 0 - не проверено, 1-для всех, 2-типо эротические материалы, 3-порно
            
            
            'pid'                   => Schema::TYPE_INTEGER . ' NULL',
            'pid_main'              => Schema::TYPE_INTEGER . ' NULL',
            'pids'                  => Schema::TYPE_STRING . '(255) NULL',
            'level'                 => Schema::TYPE_INTEGER . ' DEFAULT 0',
            'dir'                   => Schema::TYPE_TEXT . ' NULL',
            'has_children'          => Schema::TYPE_SMALLINT . '(1) DEFAULT 0',

            'main_root'              => Schema::TYPE_SMALLINT . ' NULL',
            'priority'               => Schema::TYPE_INTEGER . '  NOT NULL DEFAULT 0',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(seo_page_name);");

        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(count_comment);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(count_subscribe);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(status);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(status_adult);");

        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(pid);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(pid_main);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(pids);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(level);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(has_children);");

        $this->execute("ALTER TABLE {{%cms_tree}} ADD UNIQUE(pid, seo_page_name);");
        //$this->execute("ALTER TABLE {{%cms_tree}} ADD UNIQUE(pid_main, dir);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD UNIQUE(main_root);");

        $this->execute("ALTER TABLE {{%cms_tree}} COMMENT = 'Страницы дерево';");



        $this->addForeignKey(
            'cms_tree_pid_cms_tree', "{{%cms_tree}}",
            'pid', '{{%cms_tree}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'pid_main_pid_cms_tree', "{{%cms_tree}}",
            'pid_main', '{{%cms_tree}}', 'id', 'RESTRICT', 'RESTRICT'
        );


        $this->addForeignKey(
            'cms_tree_created_by', "{{%cms_tree}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_tree_updated_by', "{{%cms_tree}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

    }

    public function down()
    {
        $this->dropForeignKey("pid_main_pid_cms_tree", "{{%cms_tree}}");
        $this->dropForeignKey("cms_tree_pid_cms_tree", "{{%cms_tree}}");
        $this->dropForeignKey("cms_tree_created_by", "{{%cms_tree}}");
        $this->dropForeignKey("cms_tree_updated_by", "{{%cms_tree}}");

        $this->dropTable("{{%cms_tree}}");
    }
}
