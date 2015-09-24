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

class m150924_193220_alter_table__cms_user_email extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE `cms_user_email` ADD `def` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `approved`;");
        $this->execute("ALTER TABLE `cms_user_phone` ADD `def` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `approved`;");
    }

    public function safeDown()
    {
        echo "m150924_193220_alter_table__cms_user_email cannot be reverted.\n";
        return false;
    }
}