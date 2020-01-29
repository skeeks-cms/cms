<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_095515__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_content}}", "is_parent_content_required", $this->integer(1)->notNull()->defaultValue(0));
        $this->update("{{%cms_content}}", ['is_parent_content_required' => 1], ['parent_content_is_required' => 'Y']);
        $this->update("{{%cms_content}}", ['is_parent_content_required' => 0], ['parent_content_is_required' => 'N']);
        $this->dropColumn("{{%cms_content}}", "parent_content_is_required");
    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}