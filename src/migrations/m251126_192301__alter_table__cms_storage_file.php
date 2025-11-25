<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m251126_192301__alter_table__cms_storage_file extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        $tableName = 'cms_storage_file';

        $this->alterColumn($tableName, "name_to_save", $this->string(255)->null());

    }

    public function safeDown()
    {
        echo self::class;
        return false;
    }
}