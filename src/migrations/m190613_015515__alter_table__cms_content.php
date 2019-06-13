<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190613_015515__alter_table__cms_content extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_content}}", "is_count_views", $this->integer(1)->notNull()->defaultValue(0));
        $this->createIndex("is_count_views", "{{%cms_content}}", "is_count_views");
    }

    public function safeDown()
    {
        echo "m190613_015515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}