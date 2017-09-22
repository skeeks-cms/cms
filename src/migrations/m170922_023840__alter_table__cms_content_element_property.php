<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170922_023840__alter_table__cms_content_element_property extends Migration
{
    public function safeUp()
    {
        $this->createIndex("property2element2value_string", "{{%cms_content_element_property}}", ["property_id", "element_id", "value_string"]);
    }

    public function safeDown()
    {
        echo "m170922_023840__alter_table__cms_content_element_property cannot be reverted.\n";
        return false;
    }
}