<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m251127_142301__alter_table__cms_content_property_enum extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_property_enum";

        $this->addColumn($tableName, "color", $this->string(255)->comment("Цвет"));
        $this->createIndex($tableName. "__color", $tableName, ["color"]);
    }

    public function safeDown()
    {
        echo "m251127_142301__alter_table__cms_content_property_enum cannot be reverted.\n";
        return false;
    }
}