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

class m151215_193220_alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_content}}', 'access_check_element', $this->string(1)->notNull()->defaultValue('N'));
    }

    public function safeDown()
    {
        echo "m151110_193220_alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}