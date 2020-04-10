<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200410_121000__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_tree";
        $this->createIndex("cms_tree_external_id_unique", $tableName, ["cms_site_id", "external_id"], true);
    }

    public function safeDown()
    {
        echo "m200410_121000__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}