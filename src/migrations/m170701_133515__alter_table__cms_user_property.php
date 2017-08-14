<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170701_133515__alter_table__cms_user_property extends Migration
{
    public function safeUp()
    {
        $this->delete("{{%cms_user_property}}", [
            'or',
            ['element_id' => null],
            ['property_id' => null],
        ]);


        $this->dropForeignKey('cms_user_property_element_id', '{{%cms_user_property}}');
        $this->dropForeignKey('cms_user_property_property_id', '{{%cms_user_property}}');

        $this->alterColumn("{{%cms_user_property}}", 'element_id', $this->integer()->notNull());
        $this->alterColumn("{{%cms_user_property}}", 'property_id', $this->integer()->notNull());

        $this->addForeignKey(
            'cms_user_property_element_id', "{{%cms_user_property}}",
            'element_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_user_property_property_id', "{{%cms_user_property}}",
            'property_id', '{{%cms_user_universal_property}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m170701_133515__alter_table__cms_user_property cannot be reverted.\n";
        return false;
    }
}