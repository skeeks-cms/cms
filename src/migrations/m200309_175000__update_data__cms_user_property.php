<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200309_175000__update_data__cms_user_property extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_user_property";
        $tablePropertyName = "cms_user_universal_property";
        $tablePropertyEnumName = "cms_user_universal_property_enum";

        $subQuery = $this->db->createCommand("SELECT 
                    {$tableName}.id 
                FROM 
                    `{$tableName}` 
                    LEFT JOIN {$tablePropertyName} on {$tablePropertyName}.id = {$tableName}.property_id
                    LEFT JOIN {$tablePropertyEnumName} on {$tablePropertyEnumName}.id = {$tableName}.value_enum 
                where 
                    {$tablePropertyName}.property_type = 'L'")->queryAll();

        $this->update("{$tableName}", [
            'value_enum_id' => new \yii\db\Expression("{$tableName}.value_enum"),
        ], [
            "in",
            "id",
            $subQuery,
        ]);


    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}