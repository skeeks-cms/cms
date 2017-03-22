<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150528_114025_alter_table__cms_component_settings extends Migration
{
    public function safeUp()
    {
        //Возможны настройки для конкретного сайта
        $this->execute("ALTER TABLE {{%cms_component_settings%}} ADD `site_code` CHAR(5) NULL;");
        $this->execute("ALTER TABLE {{%cms_component_settings}} ADD INDEX(site_code);");

        $this->addForeignKey(
            'cms_component_settings_site_code', "{{%cms_component_settings}}",
            'site_code', '{{%cms_site}}', 'code', 'CASCADE', 'CASCADE'
        );


        //Возможны настройки для конкретного пользователя
        $this->execute("ALTER TABLE {{%cms_component_settings%}} ADD `user_id` integer(11) NULL;");
        $this->execute("ALTER TABLE {{%cms_component_settings}} ADD INDEX(user_id);");

        $this->addForeignKey(
            'cms_component_settings_user_id', "{{%cms_component_settings}}",
            'user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );


        //Возможны настройки для конкретного языка
        $this->execute("ALTER TABLE {{%cms_component_settings%}} ADD `lang_code` CHAR(5) NULL;");
        $this->execute("ALTER TABLE {{%cms_component_settings}} ADD INDEX(lang_code);");

        $this->addForeignKey(
            'cms_component_settings_lang_code', "{{%cms_component_settings}}",
            'lang_code', '{{%cms_lang}}', 'code', 'CASCADE', 'CASCADE'
        );


        $this->execute("ALTER TABLE {{%cms_component_settings%}} ADD `namespace` VARCHAR (50) NULL;");
        $this->execute("ALTER TABLE {{%cms_component_settings}} ADD INDEX(namespace);");
    }

    public function down()
    {
        echo "m150528_114025_alter_table__cms_component_settings cannot be reverted.\n";
        return false;
    }
}
