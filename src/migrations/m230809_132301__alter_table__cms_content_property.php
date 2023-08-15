<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m230809_132301__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_property";

        $this->addColumn($tableName, "is_img_offer_property", $this->integer(1)->defaultValue(0)->notNull());
        $this->createIndex($tableName.'__is_img_offer_property', $tableName, 'is_img_offer_property');
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}