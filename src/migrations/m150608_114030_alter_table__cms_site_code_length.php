<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150608_114030_alter_table__cms_site_code_length extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_site}} CHANGE `code` `code` CHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
        $this->execute("ALTER TABLE {{%cms_site_domain}} CHANGE `site_code` `site_code` CHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
        $this->execute("ALTER TABLE {{%cms_tree}} CHANGE `site_code` `site_code` CHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
        $this->execute("ALTER TABLE {{%cms_component_settings}} CHANGE `site_code` `site_code` CHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
    }

    public function down()
    {
        echo "m150608_114030_alter_table__cms_site_code_length cannot be reverted.\n";
        return false;
    }
}
