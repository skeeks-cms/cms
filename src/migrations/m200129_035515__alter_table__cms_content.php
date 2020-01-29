<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_035515__alter_table__cms_content extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_content}}", "is_active", $this->integer(1)->notNull()->defaultValue(1));
        $this->createIndex("is_active", "{{%cms_content}}", "is_active");
    }

    public function safeDown()
    {
        echo "m200129_035515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}