<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150324_273210_alter_table__cms_infoblock_2 extends Migration
{
    public function up()
    {
        $this->execute("ALTER TABLE {{%cms_infoblock%}} ADD `protected_widget_params` TEXT NULL;");
    }

    public function down()
    {
        echo "m150324_273210_alter_table__cms_infoblock_2 cannot be reverted.\n";
        return false;
    }
}
