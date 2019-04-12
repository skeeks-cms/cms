<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190412_175515__alter_table__cms_lang extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_lang}}", "is_default", $this->integer(1)->notNull()->defaultValue(0));
        $this->createIndex("is_default", "{{%cms_lang}}", "is_default");
    }

    public function safeDown()
    {
        echo "m190412_175515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}