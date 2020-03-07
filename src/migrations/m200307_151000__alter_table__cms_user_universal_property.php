<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200307_151000__alter_table__cms_user_universal_property extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_user_universal_property}}", "is_multiple", $this->integer(1)->notNull()->defaultValue(0));
        $this->update("{{%cms_user_universal_property}}", ['is_multiple' => 1], ['multiple' => 'Y']);
        $this->createIndex("is_multiple", "{{%cms_user_universal_property}}", "is_multiple");
        $this->renameColumn("{{%cms_user_universal_property}}", "multiple", "multiple__to_del");
    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}