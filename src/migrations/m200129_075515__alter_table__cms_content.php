<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_075515__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_content}}", "is_access_check_element", $this->integer(1)->notNull()->defaultValue(0));
        $this->update("{{%cms_content}}", ['is_access_check_element' => 1], ['access_check_element' => 'Y']);
        $this->dropColumn("{{%cms_content}}", "access_check_element");
    }

    public function safeDown()
    {
        echo "m200129_075515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}