<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150522_193315_drop_table__cms_infoblock extends Migration
{
    public function safeUp()
    {
        $this->dropTable('cms_infoblock');
    }

    public function down()
    {
        echo "m150522_193315_drop_table__cms_infoblock cannot be reverted.\n";
        return false;
    }
}
