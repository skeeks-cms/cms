<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200309_171000__update_data__cms_user_property extends Migration
{
    public function safeUp()
    {

        //Чистка данных

        $tableName = "cms_user_property";
        $tablePropertyName = "cms_user_universal_property";
        $tablePropertyEnumName = "cms_user_universal_property_enum";

        $subQuery = $this->db->createCommand("SELECT 
                    {$tableName}.id 
                FROM 
                    `{$tableName}` 
                    LEFT JOIN {$tablePropertyName} on {$tablePropertyName}.id = {$tableName}.property_id
                    LEFT JOIN cms_content_element on cms_content_element.id = {$tableName}.value_enum 
                where 
                    {$tablePropertyName}.property_type = 'E'
                and cms_content_element.id is null")->queryAll();

        $this->delete($tableName, [
            'in',
            'id',
            $subQuery,
        ]);

        $subQuery = $this->db->createCommand("SELECT 
	{$tableName}.id
FROM 
	`{$tableName}` 
	LEFT JOIN {$tablePropertyName} on {$tablePropertyName}.id = {$tableName}.property_id
    LEFT JOIN {$tablePropertyEnumName} on {$tablePropertyEnumName}.id = {$tableName}.value_enum 
where 
	{$tablePropertyName}.property_type = 'L'
	AND {$tablePropertyEnumName}.id is null")->queryAll();

        $this->delete($tableName, [
            'in',
            'id',
            $subQuery,
        ]);

    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}