<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m201109_111000__alter_table__cms_site extends Migration
{

    public function safeUp()
    {
        $tableName = 'cms_site';
        $this->addColumn($tableName, "internal_name", $this->string(255)->null());
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}