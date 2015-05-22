<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_143210_cms_alter_cms_tree extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_infoblock%}} DROP `status`;");

        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `status`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `status_adult`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `tree_ids`;");

        $this->execute("ALTER TABLE {{%cms_tree%}} ADD `active` CHAR(1) NOT NULL DEFAULT 'Y' ;");
        $this->execute("ALTER TABLE {{%cms_tree%}} CHANGE `seo_page_name` `code` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
    }

    public function down()
    {
        echo "m150520_143210_cms_alter_cms_tree cannot be reverted.\n";
        return false;
    }
}
