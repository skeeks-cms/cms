<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220812_100601__alter_table__cms_tree extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_tree";

        $this->addColumn($tableName, "is_adult", $this->integer(1)->defaultValue(0)->comment("Содержит контент для взрослых?"));
        $this->addColumn($tableName, "is_index", $this->integer(1)->defaultValue(1)->comment("Страница индексируется?"));

        $this->addColumn($tableName, "canonical_link", $this->string(500)->comment("Canonical на другую страницу"));
        $this->addColumn($tableName, "canonical_tree_id", $this->integer()->comment("Canonical раздел сайта"));
        $this->addColumn($tableName, "canonical_content_element_id", $this->integer()->comment("Canonical на раздел сайта"));
        $this->addColumn($tableName, "canonical_saved_filter_id", $this->integer()->comment("Canonical на сохраненный фильтр сайта"));

        $this->addColumn($tableName, "redirect_content_element_id", $this->integer()->comment("Редирект на элемент"));
        $this->addColumn($tableName, "redirect_saved_filter_id", $this->integer()->comment("Редирект на сохраненный фильтр"));

        $this->createIndex($tableName.'__is_index', $tableName, 'is_index');
        $this->createIndex($tableName.'__is_adult', $tableName, 'is_adult');

        $this->createIndex($tableName.'__canonical_tree_id', $tableName, 'canonical_tree_id');
        $this->createIndex($tableName.'__canonical_content_element_id', $tableName, 'canonical_content_element_id');
        $this->createIndex($tableName.'__canonical_saved_filter_id', $tableName, 'canonical_saved_filter_id');

        $this->createIndex($tableName.'__redirect_content_element_id', $tableName, 'redirect_content_element_id');
        $this->createIndex($tableName.'__redirect_saved_filter_id', $tableName, 'redirect_saved_filter_id');

        $this->addForeignKey(
            "{$tableName}__canonical_tree_id", $tableName,
            'canonical_tree_id', '{{%cms_tree}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__canonical_content_element_id", $tableName,
            'canonical_content_element_id', '{{%cms_content_element}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__canonical_saved_filter_id", $tableName,
            'canonical_saved_filter_id', '{{%cms_saved_filter}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__redirect_content_element_id", $tableName,
            'redirect_content_element_id', '{{%cms_content_element}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__redirect_saved_filter_id", $tableName,
            'redirect_saved_filter_id', '{{%cms_saved_filter}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}