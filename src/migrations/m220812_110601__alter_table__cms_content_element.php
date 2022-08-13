<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220812_110601__alter_table__cms_content_element extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_content_element";

        $this->addColumn($tableName, "is_adult", $this->integer(1)->defaultValue(0)->comment("Содержит контент для взрослых?"));
        $this->createIndex($tableName.'__is_adult', $tableName, 'is_adult');
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}