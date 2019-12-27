<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m191227_015615__alter_table__cms_tree extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_tree}}", "external_id", $this->string(255)->null());
        $this->createIndex("external_id", "{{%cms_tree}}", "external_id");
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}