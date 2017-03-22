<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160215_093837__create_table__cms_dashboard extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_dashboard}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_dashboard}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'name'                  => $this->string(255)->notNull(),
            'cms_user_id'           => $this->integer(),

            'priority'              => $this->integer()->notNull()->defaultValue(100),

            'columns'               => $this->integer()->notNull()->unsigned()->defaultValue(1),
            'columns_settings'      => $this->text(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_dashboard}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_dashboard}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_dashboard}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_dashboard}}', 'updated_at');

        $this->createIndex('name', '{{%cms_dashboard}}', 'name');
        $this->createIndex('cms_user_id', '{{%cms_dashboard}}', 'cms_user_id');
        $this->createIndex('priority', '{{%cms_dashboard}}', 'priority');
        $this->createIndex('columns', '{{%cms_dashboard}}', 'columns');

        $this->execute("ALTER TABLE {{%cms_dashboard}} COMMENT = 'Рабочий стол';");

        $this->addForeignKey(
            'cms_dashboard_created_by', "{{%cms_dashboard}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_dashboard__cms_user_id', "{{%cms_dashboard}}",
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_dashboard_updated_by', "{{%cms_dashboard}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_dashboard_updated_by", "{{%cms_dashboard}}");
        $this->dropForeignKey("cms_dashboard_updated_by", "{{%cms_dashboard}}");
        $this->dropForeignKey("cms_dashboard__cms_user_id", "{{%cms_dashboard}}");

        $this->dropTable("{{%cms_dashboard}}");
    }
}