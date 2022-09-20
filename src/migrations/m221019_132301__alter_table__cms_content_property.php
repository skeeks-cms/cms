<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m221019_132301__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_property";

        $this->addColumn($tableName, "is_country", $this->integer(1)->comment("Страна"));
        $this->addColumn($tableName, "is_vendor", $this->integer(1)->comment("Производитель"));
        $this->addColumn($tableName, "is_vendor_code", $this->integer(1)->comment("Код производителя"));
        $this->addColumn($tableName, "is_offer_property", $this->integer(1)->comment("Свойство модификации"));

        $this->createIndex("is_vendor", $tableName, ["is_vendor", "cms_site_id"], true);
        $this->createIndex("is_vendor_code", $tableName, ["is_vendor_code", "cms_site_id"], true);
        $this->createIndex("is_country", $tableName, ["is_country", "cms_site_id"], true);
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}