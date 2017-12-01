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

class m151030_183220_alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_tree}}', 'redirect_code', $this->integer() . " NOT NULL DEFAULT 301");
        $this->createIndex('redirect_code', '{{%cms_tree}}', 'redirect_code');
    }

    public function safeDown()
    {
        echo "m151030_183220_alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}