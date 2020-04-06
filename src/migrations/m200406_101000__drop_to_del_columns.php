<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200406_101000__drop_to_del_columns extends Migration
{
    public function safeUp()
    {
        $this->dropColumn("cms_site", "server_name__to_dell");
    }

    public function safeDown()
    {
        echo "m200406_101000__drop_to_del_columns cannot be reverted.\n";
        return false;
    }
}