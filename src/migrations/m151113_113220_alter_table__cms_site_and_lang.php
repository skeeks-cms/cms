<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m151113_113220_alter_table__cms_site_and_lang extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE `cms_site` ADD `image_id` INT(11) NULL AFTER `description`;");
        $this->execute("ALTER TABLE `cms_lang` ADD `image_id` INT(11) NULL AFTER `description`;");

        $this->addForeignKey(
            'cms_site__image_id', "{{%cms_site}}",
            'image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            'cms_lang__image_id', "{{%cms_lang}}",
            'image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m151113_113220_alter_table__cms_site_and_lang cannot be reverted.\n";
        return false;
    }
}