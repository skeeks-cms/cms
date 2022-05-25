<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m220127_152615__alter_table__cms_user extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_user}}", "is_active", $this->integer(1)->notNull()->defaultValue(1));
        $this->addColumn("{{%cms_user}}", "alias", $this->string(255)->comment("Псевдоним"));

        $this->createIndex("is_active", "{{%cms_user}}", "is_active");
        $this->createIndex("alias", "{{%cms_user}}", "alias");
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}