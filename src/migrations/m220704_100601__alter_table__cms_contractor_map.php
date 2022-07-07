<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220704_100601__alter_table__cms_contractor_map extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_contractor_map";

        $this->createIndex($tableName.'__cms_contractor_tmp_id', $tableName, ['cms_contractor_id']);

        $this->dropIndex($tableName.'__cms_contractor_id', $tableName);
        $this->createIndex($tableName.'__uniq_user_contractor', $tableName, ['cms_contractor_id', 'cms_user_id'], true);

        $this->dropIndex($tableName.'__cms_contractor_tmp_id', $tableName);
        $this->createIndex($tableName.'__cms_contractor_id', $tableName, ['cms_contractor_id']);
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}