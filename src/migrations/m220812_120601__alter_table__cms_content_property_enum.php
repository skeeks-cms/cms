<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220812_120601__alter_table__cms_content_property_enum extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_content_property_enum";

        $this->addColumn($tableName, "value_for_saved_filter", $this->string(255)->comment("Название (для сохраненных фильтров)"));
        $this->addColumn($tableName, "description", $this->text()->comment("Описание"));
        $this->addColumn($tableName, "cms_image_id", $this->integer()->comment("Фото/Изображение"));

        $this->createIndex($tableName.'__value_for_saved_filter', $tableName, 'value_for_saved_filter');
        $this->createIndex($tableName.'__cms_image_id', $tableName, 'cms_image_id');

        $this->addForeignKey(
            "{$tableName}__cms_image_id", $tableName,
            'cms_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}