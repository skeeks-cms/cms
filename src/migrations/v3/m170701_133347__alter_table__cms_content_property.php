<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170701_133347__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey("cms_content_property_created_by", "{{%cms_content_property}}");
        $this->dropForeignKey("cms_content_property_updated_by", "{{%cms_content_property}}");

        $this->addForeignKey(
            'cms_content_property__created_by', "{{%cms_content_property}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            'cms_content_property__updated_by', "{{%cms_content_property}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m170701_133344__alter_table__cms_tree_property cannot be reverted.\n";
        return false;
    }
}