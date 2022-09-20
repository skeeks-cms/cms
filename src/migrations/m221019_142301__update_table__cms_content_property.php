<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m221019_142301__update_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_property";

        $subQuery = $this->db->createCommand("
            UPDATE 
                `cms_content_property` as c
                INNER JOIN shop_cms_content_property as sc ON sc.cms_content_property_id = c.id 
            SET 
                c.is_vendor = sc.is_vendor
        ")->execute();

        $subQuery = $this->db->createCommand("
            UPDATE 
                `cms_content_property` as c
                INNER JOIN shop_cms_content_property as sc ON sc.cms_content_property_id = c.id 
            SET 
                c.is_vendor_code = sc.is_vendor_code
        ")->execute();

        $subQuery = $this->db->createCommand("
            UPDATE 
                `cms_content_property` as c
                INNER JOIN shop_cms_content_property as sc ON sc.cms_content_property_id = c.id 
            SET 
                c.is_offer_property = sc.is_offer_property
        ")->execute();
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}