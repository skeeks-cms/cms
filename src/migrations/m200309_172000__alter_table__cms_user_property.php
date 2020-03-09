<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200309_172000__alter_table__cms_user_property extends Migration
{
    public function safeUp()
    {

        $tableName = "cms_user_property";
        $tablePropertyName = "cms_user_universal_property";
        $tablePropertyEnumName = "cms_user_universal_property_enum";

        $this->addColumn($tableName, "value_element_id", $this->integer());

        $this->createIndex("value_element_id", $tableName, "value_element_id");

        $this->addForeignKey(
            "{$tableName}__value_cms_element_id", $tableName,
            'value_element_id', '{{%cms_content_element}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}