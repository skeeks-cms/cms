<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150827_133220_create_table__cms_search_phrase extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_search_phrase}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_search_phrase}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'phrase'                => $this->string(255),

            'result_count'          => $this->integer()->notNull()->defaultValue(0),
            'pages'                 => $this->integer()->notNull()->defaultValue(0),

            'ip'                    => $this->string(32),
            'session_id'            => $this->string(32),
            'site_code'             => "CHAR(15) NULL",

            'data_server'           => $this->text(),
            'data_session'          => $this->text(),
            'data_cookie'           => $this->text(),
            'data_request'          => $this->text(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_search_phrase}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_search_phrase}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_search_phrase}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_search_phrase}}', 'updated_at');

        $this->createIndex('phrase', '{{%cms_search_phrase}}', 'phrase');
        $this->createIndex('result_count', '{{%cms_search_phrase}}', 'result_count');
        $this->createIndex('pages', '{{%cms_search_phrase}}', 'pages');
        $this->createIndex('ip', '{{%cms_search_phrase}}', 'ip');
        $this->createIndex('session_id', '{{%cms_search_phrase}}', 'session_id');
        $this->createIndex('site_code', '{{%cms_search_phrase}}', 'site_code');

        $this->execute("ALTER TABLE {{%cms_search_phrase}} COMMENT = 'Поисковые фразы';");

        $this->addForeignKey(
            'cms_search_phrase_created_by', "{{%cms_search_phrase}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_search_phrase_updated_by', "{{%cms_search_phrase}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_search_phrase_site_code_fk', "{{%cms_search_phrase}}",
            'site_code', '{{%cms_site}}', 'code', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_search_phrase_updated_by", "{{%cms_search_phrase}}");
        $this->dropForeignKey("cms_search_phrase_updated_by", "{{%cms_search_phrase}}");
        $this->dropForeignKey("cms_search_phrase_site_code_fk", "{{%cms_search_phrase}}");

        $this->dropTable("{{%cms_search_phrase}}");
    }
}