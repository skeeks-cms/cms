<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240223_132301__alter_table__cms_saved_filter extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_saved_filter";

        $this->createIndex($tableName.'__uniq_tree2brand', $tableName, ['shop_brand_id', 'cms_tree_id'], true);
        $this->createIndex($tableName.'__uniq_tree2country', $tableName, ['country_alpha2', 'cms_tree_id'], true);
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}