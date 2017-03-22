<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150528_114030_alter_table__cms_component_settings extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE cms_component_settings DROP INDEX component_2;");
    }

    public function down()
    {
        echo "m150528_114030_alter_table__cms_component_settings cannot be reverted.\n";
        return false;
    }
}
