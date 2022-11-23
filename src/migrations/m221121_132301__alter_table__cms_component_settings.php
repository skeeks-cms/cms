<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m221121_132301__alter_table__cms_component_settings extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_component_settings";
        $this->execute("ALTER TABLE `cms_component_settings` CHANGE `value` `value` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;");
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}