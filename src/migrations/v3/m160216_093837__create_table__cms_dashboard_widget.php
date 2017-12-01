<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160216_093837__create_table__cms_dashboard_widget extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_dashboard_widget}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_dashboard_widget}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'cms_dashboard_id'      => $this->integer()->notNull(),
            'cms_dashboard_column'  => $this->integer()->notNull()->defaultValue(1),

            'priority'              => $this->integer()->notNull()->defaultValue(100),

            'component'             => $this->string(255)->notNull(),
            'component_settings'    => $this->text(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_dashboard_widget}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_dashboard_widget}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_dashboard_widget}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_dashboard_widget}}', 'updated_at');

        $this->createIndex('priority', '{{%cms_dashboard_widget}}', 'priority');
        $this->createIndex('component', '{{%cms_dashboard_widget}}', 'component');
        $this->createIndex('cms_dashboard_id', '{{%cms_dashboard_widget}}', 'cms_dashboard_id');
        $this->createIndex('cms_dashboard_column', '{{%cms_dashboard_widget}}', 'cms_dashboard_column');

        $this->execute("ALTER TABLE {{%cms_dashboard_widget}} COMMENT = 'Виджет рабочего стола';");

        $this->addForeignKey(
            'cms_dashboard_widget_created_by', "{{%cms_dashboard_widget}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_dashboard_widget_updated_by', "{{%cms_dashboard_widget}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_dashboard_widget__cms_dashboard_id', "{{%cms_dashboard_widget}}",
            'cms_dashboard_id', '{{%cms_dashboard}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_dashboard_widget_updated_by", "{{%cms_dashboard_widget}}");
        $this->dropForeignKey("cms_dashboard_widget_updated_by", "{{%cms_dashboard_widget}}");
        $this->dropForeignKey("cms_dashboard_widget__cms_dashboard_id", "{{%cms_dashboard_widget}}");

        $this->dropTable("{{%cms_dashboard_widget}}");
    }
}