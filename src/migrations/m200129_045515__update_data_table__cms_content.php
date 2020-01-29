<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_045515__update_data_table__cms_content extends Migration
{

    public function safeUp()
    {
        $this->update("{{%cms_content}}", ['is_active' => 1], ['active' => 'Y']);
        $this->update("{{%cms_content}}", ['is_active' => 0], ['active' => 'N']);
    }

    public function safeDown()
    {
        echo "m200129_045515__update_data_table__cms_content cannot be reverted.\n";
        return false;
    }
}