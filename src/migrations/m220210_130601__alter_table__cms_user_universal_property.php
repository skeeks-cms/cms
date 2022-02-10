<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220210_130601__alter_table__cms_user_universal_property extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_user_universal_property";

        $this->dropIndex("code", $tableName);
        $this->createIndex("code2cms_site_id", $tableName, ['code', 'cms_site_id'], true);
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}