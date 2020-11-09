<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m201109_121000__alter_table__cms_content extends Migration
{

    public function safeUp()
    {
        $tableName = 'cms_content';
        $this->addColumn($tableName, "is_show_on_all_sites", $this->integer(1)->defaultValue(0)->comment("Элементы контента показывать на всех сайтах?"));
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}