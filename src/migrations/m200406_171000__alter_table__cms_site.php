<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200406_171000__alter_table__cms_site extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_site";

        $this->addColumn($tableName, "is_active", $this->integer(1)->unsigned()->notNull()->defaultValue(1));
        $this->update($tableName, ['is_active' => 0], ['active' => 'N']);

        $this->addColumn($tableName, "is_default", $this->integer(1)->unsigned());
        $this->update($tableName, ['is_default' => 1], ['def' => 'Y']);

        $this->createIndex("is_default", $tableName, ["is_default"], true);

        $this->dropColumn($tableName, "def");
        $this->dropColumn($tableName, "active");

        $this->renameColumn($tableName, "code", "_to_del_code");
    }

    public function safeDown()
    {
        echo "m200406_101000__drop_to_del_columns cannot be reverted.\n";
        return false;
    }
}