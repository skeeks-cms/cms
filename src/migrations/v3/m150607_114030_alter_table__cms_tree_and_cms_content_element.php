<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150607_114030_alter_table__cms_tree_and_cms_content_element extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree}} ADD `description_short_type` VARCHAR(10) NOT NULL DEFAULT 'text';");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD `description_full_type` VARCHAR(10) NOT NULL DEFAULT 'text';");

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD `description_short_type` VARCHAR(10) NOT NULL DEFAULT 'text';");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD `description_full_type` VARCHAR(10) NOT NULL DEFAULT 'text';");



        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(description_short_type);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(description_full_type);");


        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(description_short_type);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(description_full_type);");

    }

    public function down()
    {
        echo "m150607_114030_alter_table__cms_tree_and_cms_content_element cannot be reverted.\n";
        return false;
    }
}
