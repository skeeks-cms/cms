<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150922_213220_alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_user}} ADD `image_id` INT(11) NULL DEFAULT NULL AFTER `name`;");
        $this->createIndex('image_id', '{{%cms_user}}', 'image_id');

        $this->addForeignKey(
            'cms_user__image_id', "{{%cms_user}}",
            'image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        echo "m150922_213220_alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}
