<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_153210_cms_alter_meta_data extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree%}} ADD `meta_title` VARCHAR(500) NULL , ADD INDEX (`meta_title`) ;");
        $this->execute("ALTER TABLE {{%cms_tree%}} ADD `meta_description` TEXT NULL ;");
        $this->execute("ALTER TABLE {{%cms_tree%}} ADD `meta_keywords` TEXT NULL ;");


        $this->execute("ALTER TABLE {{%cms_content_element%}} ADD `meta_title` VARCHAR(500) NOT NULL , ADD INDEX (`meta_title`) ;");
        $this->execute("ALTER TABLE {{%cms_content_element%}} ADD `meta_description` TEXT NULL ;");
        $this->execute("ALTER TABLE {{%cms_content_element%}} ADD `meta_keywords` TEXT NULL ;");
    }

    public function down()
    {
        echo "m150520_153210_cms_alter_meta_data cannot be reverted.\n";
        return false;
    }
}
