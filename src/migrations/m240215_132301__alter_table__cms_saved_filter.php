<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240215_132301__alter_table__cms_saved_filter extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_saved_filter";

        $this->alterColumn($tableName, "cms_content_property_id", $this->integer(11)->null()->comment("Характеристика"));

        $this->addColumn($tableName, "country_alpha2", $this->string(2)->null()->comment("Страна"));
        $this->addColumn($tableName, "shop_brand_id", $this->integer(11)->null()->comment("Бренд"));

        $this->createIndex($tableName.'__shop_brand_id', $tableName, 'shop_brand_id');
        $this->createIndex($tableName.'__country_alpha2', $tableName, 'country_alpha2');

        $this->addForeignKey(
            "{$tableName}__shop_brand_id", $tableName,
            'shop_brand_id', '{{%shop_brand}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            "{$tableName}__country_alpha2", $tableName,
            'country_alpha2', '{{%cms_country}}', 'alpha2', 'RESTRICT', 'RESTRICT'
        );

    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}