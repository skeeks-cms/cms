<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150922_233220_alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree}} ADD `image_full_id` INT(11) NULL DEFAULT NULL AFTER `name`;");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD `image_id` INT(11) NULL DEFAULT NULL AFTER `name`;");
        $this->createIndex('image_id', '{{%cms_tree}}', 'image_id');
        $this->createIndex('image_full_id', '{{%cms_tree}}', 'image_full_id');

        $this->addForeignKey(
            'cms_tree__image_id', "{{%cms_tree}}",
            'image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree__image_full_id', "{{%cms_tree}}",
            'image_full_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        echo "m150922_233220_alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}
