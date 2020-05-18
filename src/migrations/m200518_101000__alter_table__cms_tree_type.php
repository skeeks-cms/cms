<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200518_101000__alter_table__cms_tree_type extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_tree_type}}", "is_active", $this->integer(1)->notNull()->defaultValue(1));
        $this->createIndex("cms_tree_type__is_active", "{{%cms_tree_type}}", "is_active");

        $this->update("{{%cms_tree_type}}", ['is_active' => 0], ['active' => 'N']);

        $this->dropColumn("{{%cms_tree_type}}", "active");
        $this->dropColumn("{{%cms_tree_type}}", "index_for_search");
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}