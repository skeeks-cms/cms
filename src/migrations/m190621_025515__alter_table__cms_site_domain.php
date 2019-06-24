<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190621_025515__alter_table__cms_site_domain extends Migration
{

    public function safeUp()
    {
        $this->renameColumn("{{%cms_site}}", "server_name", "server_name__to_dell");
    }

    public function safeDown()
    {
        echo "m190621_025515__alter_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}