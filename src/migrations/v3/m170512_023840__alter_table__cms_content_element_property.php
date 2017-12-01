<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170512_023840__alter_table__cms_content_element_property extends Migration
{
    public function safeUp()
    {
        $this->createIndex("property2element", "{{%cms_content_element_property}}", ["property_id", "element_id"]);
        $this->createIndex("property2element2value_enum", "{{%cms_content_element_property}}", ["property_id", "element_id", "value_enum"]);
        $this->createIndex("property2element2value_num", "{{%cms_content_element_property}}", ["property_id", "element_id", "value_num"]);
    }

    public function safeDown()
    {
        echo "m170512_023840__alter_table__cms_content_element_property cannot be reverted.\n";
        return false;
    }
}