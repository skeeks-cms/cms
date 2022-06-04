<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220603_100601__alter_table__cms_user extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_user";

        $this->addColumn($tableName, "is_company", $this->integer(1)->defaultValue(0)->comment("Аккаунт компании?"));
        $this->addColumn($tableName, "company_name", $this->string(255)->comment("Название компании"));

        $this->createIndex("is_company", $tableName, ['is_company']);
        $this->createIndex("company_name", $tableName, ['company_name']);
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}