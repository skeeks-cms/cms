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

class m151023_113220_alter_table__cms_site extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE `cms_site` DROP FOREIGN KEY cms_site_lang_code;");
        $this->execute("ALTER TABLE `cms_site` DROP `lang_code`;");
    }

    public function safeDown()
    {
        echo "m150924_193220_alter_table__cms_user_email cannot be reverted.\n";
        return false;
    }
}