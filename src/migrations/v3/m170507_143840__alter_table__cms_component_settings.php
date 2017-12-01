<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170507_143840__alter_table__cms_component_settings extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey("cms_component_settings_site_code", "{{%cms_component_settings}}");
        $this->dropForeignKey("cms_component_settings_lang_code", "{{%cms_component_settings}}");

        $this->dropColumn("{{%cms_component_settings}}", "site_code");
        $this->dropColumn("{{%cms_component_settings}}", "lang_code");
    }

    public function safeDown()
    {
        echo "m170507_103840__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}