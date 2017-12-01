<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150521_193315_alter_table__cms_settings extends Migration
{
    public function safeUp()
    {
        $this->renameTable("cms_settings", "cms_component_settings");
    }

    public function down()
    {
        echo "m150521_193315_alter_table__cms_settings cannot be reverted.\n";
        return false;
    }
}
