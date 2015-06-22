<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150622_114030_alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_user}} ADD `logged_at` INT NULL ;");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(logged_at);");
    }

    public function down()
    {
        echo "m150622_114030_alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}
