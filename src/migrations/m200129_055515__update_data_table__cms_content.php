<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_055515__update_data_table__cms_content extends Migration
{

    public function safeUp()
    {
        $this->update("{{%cms_content}}", ['is_allow_change_tree' => 1], ['is_allow_change_tree' => 'Y']);
        $this->update("{{%cms_content}}", ['is_allow_change_tree' => 0], ['is_allow_change_tree' => 'N']);
    }

    public function safeDown()
    {
        echo "m200129_045515__update_data_table__cms_content cannot be reverted.\n";
        return false;
    }
}