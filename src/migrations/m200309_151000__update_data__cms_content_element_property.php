<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200309_151000__update_data__cms_content_element_property extends Migration
{
    public function safeUp()
    {

        //Чистка данных

        $tableName = "cms_content_element_property";

        $subQuery = $this->db->createCommand("
            DELETE FROM 
                cms_content_element_property USING cms_content_element_property 
                LEFT JOIN cms_content_property on cms_content_property.id = cms_content_element_property.property_id 
                LEFT JOIN cms_content_element on cms_content_element.id = cms_content_element_property.value_enum 
            WHERE 
                cms_content_property.property_type = 'E' AND 
                cms_content_element.id is null
        ")->execute();


        $subQuery = $this->db->createCommand("
            DELETE FROM 
                cms_content_element_property USING cms_content_element_property 
                LEFT JOIN cms_content_property on cms_content_property.id = cms_content_element_property.property_id
                LEFT JOIN cms_content_property_enum on cms_content_property_enum.id = cms_content_element_property.value_enum 
            WHERE 
                cms_content_property.property_type = 'L' AND 
                cms_content_property_enum.id is null
        ")->execute();


        //Memory limit problems
        /*$subQuery = $this->db->createCommand("SELECT
                    {$tableName}.id 
                FROM 
                    `{$tableName}` 
                    LEFT JOIN cms_content_property on cms_content_property.id = {$tableName}.property_id
                    LEFT JOIN cms_content_element on cms_content_element.id = {$tableName}.value_enum 
                where 
                    cms_content_property.property_type = 'E'
                and cms_content_element.id is null")->queryAll();

        $this->delete($tableName, [
            'in',
            'id',
            $subQuery,
        ]);*/

        /*$subQuery = $this->db->createCommand("SELECT
	{$tableName}.id
FROM 
	`{$tableName}` 
	LEFT JOIN cms_content_property on cms_content_property.id = {$tableName}.property_id
    LEFT JOIN cms_content_property_enum on cms_content_property_enum.id = {$tableName}.value_enum 
where 
	cms_content_property.property_type = 'L'
	AND cms_content_property_enum.id is null")->queryAll();

        $this->delete("{{%cms_content_element_property}}", [
            'in',
            'id',
            $subQuery,
        ]);*/

    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}