<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220126_021000__alter_table__cms_user extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_user";

        $this->dropIndex("username", $tableName);
        $this->dropIndex("email", $tableName);
        $this->dropIndex("phone", $tableName);

        $this->createIndex("site_username", $tableName, ['cms_site_id', 'username'], true);
        $this->createIndex("site_email", $tableName, ['cms_site_id', 'email'], true);
        $this->createIndex("site_phone", $tableName, ['cms_site_id', 'phone'], true);
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}