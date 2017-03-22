<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150707_114030_alter_table__big_text extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_component_settings}} CHANGE `value` `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");

        $this->execute("ALTER TABLE {{%cms_content_element}} CHANGE `description_short` `description_short` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE {{%cms_content_element}} CHANGE `description_full` `description_full` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE {{%cms_content_element}} CHANGE `files` `files` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");


        $this->execute("ALTER TABLE {{%cms_tree}} CHANGE `description_short` `description_short` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE {{%cms_tree}} CHANGE `description_full` `description_full` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE {{%cms_tree}} CHANGE `files` `files` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");

        $this->execute("ALTER TABLE {{%cms_content_property}} CHANGE `component_settings` `component_settings` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE {{%cms_tree_type_property}} CHANGE `component_settings` `component_settings` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");

        $this->dropIndex('value', '{{%cms_content_element_property}}');
        $this->dropIndex('value', '{{%cms_tree_property}}');

        $this->execute("ALTER TABLE {{%cms_content_element_property}} CHANGE `value` `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
        $this->execute("ALTER TABLE {{%cms_tree_property}} CHANGE `value` `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
    }

    public function down()
    {
        echo "m150707_114030_alter_table__big_text cannot be reverted.\n";
        return false;
    }
}
