<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m210903_120601__alter_table__cms_content extends Migration
{

    public function safeUp()
    {
        $tableName = 'cms_content';

        $this->addColumn($tableName, "cms_tree_type_id", $this->integer()->comment("Элементы контента относятся к разделам этого тип"));

        $this->addColumn($tableName, "saved_filter_tree_type_id", $this->integer()->comment("Это контент посадочных страниц"));

        $this->createIndex("cms_tree_type_id", $tableName, ['cms_tree_type_id']);
        $this->createIndex("saved_filter_tree_type_id", $tableName, ['saved_filter_tree_type_id']);

        $this->addForeignKey(
            "{$tableName}__cms_tree_type_id", $tableName,
            'cms_tree_type_id', '{{%cms_tree_type}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__saved_filter_tree_type_id", $tableName,
            'saved_filter_tree_type_id', '{{%cms_tree_type}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}