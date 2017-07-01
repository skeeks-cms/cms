<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170701_133357__alter_table__cms_content_property_enum extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey("cms_content_property_enum_created_by", "{{%cms_content_property_enum}}");
        $this->dropForeignKey("cms_content_property_enum_updated_by", "{{%cms_content_property_enum}}");

        $this->addForeignKey(
            'cms_content_property_enum__created_by', "{{%cms_content_property_enum}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            'cms_content_property_enum__updated_by', "{{%cms_content_property_enum}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m170701_133355__alter_table__cms_content_element_tree cannot be reverted.\n";
        return false;
    }
}