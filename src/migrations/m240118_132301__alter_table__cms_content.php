<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240118_132301__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content";

        $this->addColumn($tableName, "base_role", $this->string(255)->defaultValue(null)->null()->comment("Базовая роль (товары, бренды, коллекции и т.д.)"));
        $this->createIndex($tableName.'__base_content_type', $tableName, 'base_role', true);
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}