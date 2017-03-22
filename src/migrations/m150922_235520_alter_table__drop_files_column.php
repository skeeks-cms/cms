<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150922_235520_alter_table__drop_files_column extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree_type}} DROP `files`;");
        $this->execute("ALTER TABLE {{%cms_content}} DROP `files`;");
    }

    public function down()
    {
        echo "m150922_235520_alter_table__drop_files_column cannot be reverted.\n";
        return false;
    }
}
