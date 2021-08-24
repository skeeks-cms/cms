<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m210821_120601__create_table__cms_saved_filter extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_saved_filter';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [

            'id' => $this->primaryKey(),

            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),

            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),


            'cms_site_id' => $this->integer()->notNull(),
            'cms_tree_id' => $this->integer()->notNull(),

            'cms_content_property_id'       => $this->integer()->notNull(),

            'value_content_element_id'       => $this->integer(),
            'value_content_property_enum_id' => $this->integer(),

            'cms_image_id' => $this->integer(),

            'name' => $this->string(255),

            'code' => $this->string(255),

            'description_short' => 'LONGTEXT NULL',
            'description_full'  => 'LONGTEXT NULL',

            'meta_title'       => $this->string(500),
            'meta_description' => $this->text(),
            'meta_keywords'    => $this->text(),

            'description_short_type' => $this->string(10)->defaultValue("text")->notNull(),
            'description_full_type'  => $this->string(10)->defaultValue("text")->notNull(),

            'seo_h1' => $this->string(255),

            'priority' => $this->integer()->notNull()->defaultValue(500),

        ], $tableOptions);


        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');


        $this->createIndex($tableName.'__cms_site_id', $tableName, 'cms_site_id');
        $this->createIndex($tableName.'__cms_tree_id', $tableName, ['cms_tree_id']);
        $this->createIndex($tableName.'__cms_image_id', $tableName, ['cms_image_id']);
        $this->createIndex($tableName.'__priority', $tableName, ['priority']);

        $this->createIndex($tableName.'__code2site', $tableName, ['code', 'cms_site_id']);

        $this->createIndex($tableName.'__tree2element', $tableName, ['cms_tree_id', 'value_content_element_id']);
        $this->createIndex($tableName.'__tree2enum', $tableName, ['cms_tree_id', 'value_content_property_enum_id']);

        $this->addCommentOnTable($tableName, 'Сохраненные фильтры');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        //Удаляя сайт - удаляются и все сохраненные фильтры
        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );

        //Удаляя раздел - удаляются и все сохраненные фильтры
        $this->addForeignKey(
            "{$tableName}__cms_tree_id", $tableName,
            'cms_tree_id', '{{%cms_tree}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_image_id", $tableName,
            'cms_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_content_property_id", $tableName,
            'cms_content_property_id', '{{%cms_content_property}}', 'id', 'RESTRICT', 'RESTRICT'
        );
        $this->addForeignKey(
            "{$tableName}__value_content_element_id", $tableName,
            'value_content_element_id', '{{%cms_content_element}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            "{$tableName}__value_content_property_enum_id", $tableName,
            'value_content_property_enum_id', '{{%cms_content_property_enum}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}