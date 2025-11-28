<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m251127_132301__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_property";

        $this->addColumn($tableName, "is_sticker", $this->integer(1)->comment("Стикер"));
        $this->createIndex($tableName. "__is_sticker", $tableName, ["is_sticker", "cms_site_id"], true);
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}