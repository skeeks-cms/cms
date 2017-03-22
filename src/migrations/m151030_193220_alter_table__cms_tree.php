<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m151030_193220_alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_tree}}', 'name_hidden', $this->string(255));
        $this->createIndex('name_hidden', '{{%cms_tree}}', 'name_hidden');
    }

    public function safeDown()
    {
        echo "m151030_193220_alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}