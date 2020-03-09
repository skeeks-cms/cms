<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200309_161000__update_data__cms_tree_property extends Migration
{
    public function safeUp()
    {

        //Чистка данных

        $tableName = "cms_tree_property";

        $subQuery = $this->db->createCommand("SELECT 
                    {$tableName}.id 
                FROM 
                    `{$tableName}` 
                    LEFT JOIN cms_tree_type_property on cms_tree_type_property.id = {$tableName}.property_id
                    LEFT JOIN cms_content_element on cms_content_element.id = {$tableName}.value_enum 
                where 
                    cms_tree_type_property.property_type = 'E'
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
	LEFT JOIN cms_tree_type_property on cms_tree_type_property.id = {$tableName}.property_id
    LEFT JOIN cms_tree_type_property_enum on cms_tree_type_property_enum.id = {$tableName}.value_enum 
where 
	cms_tree_type_property.property_type = 'L'
	AND cms_tree_type_property_enum.id is null")->queryAll();

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