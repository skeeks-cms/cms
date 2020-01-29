<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_085515__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_content}}", "is_visible", $this->integer(1)->notNull()->defaultValue(1));
        $this->update("{{%cms_content}}", ['is_visible' => 1], ['visible' => 'Y']);
        $this->update("{{%cms_content}}", ['is_visible' => 0], ['visible' => 'N']);
        $this->dropColumn("{{%cms_content}}", "visible");
    }

    public function safeDown()
    {
        echo "m200129_075515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}