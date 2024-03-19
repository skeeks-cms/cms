<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240319_132301__alter_table__cms_storage_file_sx_data extends Migration
{
    public function safeUp()
    {
        $this->addColumn("cms_storage_file", "sx_data", $this->text()->null());
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}