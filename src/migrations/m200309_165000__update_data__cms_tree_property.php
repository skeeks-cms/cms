<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200309_165000__update_data__cms_tree_property extends Migration
{
    public function safeUp()
    {

        $tableName = 'cms_tree_property';

        $subQuery = $this->db->createCommand("SELECT 
                    {$tableName}.id 
                FROM 
                    `{$tableName}` 
                    LEFT JOIN cms_tree_type_property on cms_tree_type_property.id = {$tableName}.property_id
                    LEFT JOIN cms_tree_type_property_enum on cms_tree_type_property_enum.id = {$tableName}.value_enum 
                where 
                    cms_tree_type_property.property_type = 'L'")->queryAll();

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