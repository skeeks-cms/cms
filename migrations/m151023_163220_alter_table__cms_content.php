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

class m151023_163220_alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_content}}', 'viewFile', $this->string(255));
        $this->createIndex('viewFile', '{{%cms_content}}', 'viewFile');
    }

    public function safeDown()
    {
        echo "m151023_153220_alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}