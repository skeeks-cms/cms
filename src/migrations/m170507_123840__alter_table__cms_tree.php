<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170507_123840__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->alterColumn("{{%cms_tree}}", 'dir', $this->string(500));
        $this->createIndex("cms_tree__site_dir", "{{%cms_tree}}", ['dir', 'cms_site_id']);
    }

    public function safeDown()
    {
        echo "m170507_103840__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}