<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.02.2016
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m160221_193220__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_tree}}', 'view_file', $this->string(128));
        $this->createIndex('view_file', '{{%cms_tree}}', 'view_file');
    }

    public function safeDown()
    {
        $this->dropIndex('view_file', '{{%cms_tree}}');
        $this->dropColumn('{{%cms_tree}}', 'view_file');
    }
}