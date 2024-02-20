<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240122_132301__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_tree";

        $this->addColumn($tableName, "shop_has_collections", $this->integer(1)->defaultValue(0)->notNull()->comment("Раздел содержит товарные коллекции"));
        $this->addColumn($tableName, "shop_show_collections", $this->integer(1)->defaultValue(1)->notNull()->comment("Показывать коллекции по умолчанию"));

        $this->createIndex($tableName.'__shop_has_collections', $tableName, 'shop_has_collections');
        $this->createIndex($tableName.'__shop_show_collections', $tableName, 'shop_show_collections');
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}