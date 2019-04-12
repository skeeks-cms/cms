<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190412_215515__update_data_table__cms_lang extends Migration
{
    public function safeUp()
    {
        $this->update("{{%cms_lang}}", ['is_active' => 1], ['active' => 'Y']);
    }

    public function safeDown()
    {
        echo "m190412_185515__update_data_table__cms_lang cannot be reverted.\n";
        return false;
    }
}