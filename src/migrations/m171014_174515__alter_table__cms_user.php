<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m171014_174515__alter_table__cms_user extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_user}}", "first_name", $this->string(255));
        $this->createIndex("first_name", "{{%cms_user}}", "first_name");

        $this->addColumn("{{%cms_user}}", "last_name", $this->string(255));
        $this->createIndex("last_name", "{{%cms_user}}", "last_name");

        $this->addColumn("{{%cms_user}}", "patronymic", $this->string(255));
        $this->createIndex("patronymic", "{{%cms_user}}", "patronymic");

        $this->createIndex("full_name_index", "{{%cms_user}}", ["first_name", "last_name", "patronymic"]);

        $this->renameColumn("{{%cms_user}}", "name", "_to_del_name");
    }

    public function safeDown()
    {
        echo "m171014_174515__alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}