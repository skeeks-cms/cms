<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240121_132301__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content";

        $this->addColumn($tableName, "is_tree_only_max_level", $this->integer(1)->defaultValue(1)->notNull()->comment("Разрешено привязывать только к разделам, без подразделов"));
        $this->addColumn($tableName, "is_tree_only_no_redirect", $this->integer(1)->defaultValue(1)->notNull()->comment("Разрешено привязывать только к разделам, не редирректам"));
        $this->addColumn($tableName, "is_tree_required", $this->integer(1)->defaultValue(0)->notNull()->comment("Раздел необходимо выбирать обязательно"));
        $this->addColumn($tableName, "is_tree_allow_change", $this->integer(1)->defaultValue(1)->notNull()->comment("Разраешено менять раздел при редактировании"));

        $this->createIndex($tableName.'__is_tree_only_max_level', $tableName, 'is_tree_only_max_level');
        $this->createIndex($tableName.'__is_tree_only_no_redirect', $tableName, 'is_tree_only_no_redirect');
        $this->createIndex($tableName.'__is_tree_required', $tableName, 'is_tree_required');
        $this->createIndex($tableName.'__is_tree_allow_change', $tableName, 'is_tree_allow_change');
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}