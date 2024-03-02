<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240302_132301__alter_tables__add_sx_id extends Migration
{
    public function safeUp()
    {
        $this->addColumn("cms_content_element", "sx_id", $this->integer()->null());
        $this->createIndex('cms_content_element__sx_id', "cms_content_element", ['sx_id'], true);

        $this->addColumn("cms_tree", "sx_id", $this->integer()->null());
        $this->createIndex('cms_tree__sx_id', "cms_tree", ['sx_id'], true);

        $this->addColumn("cms_content_property", "sx_id", $this->integer()->null());
        $this->createIndex('cms_content_property__sx_id', "cms_content_property", ['sx_id'], true);

        $this->addColumn("cms_content_property_enum", "sx_id", $this->integer()->null());
        $this->createIndex('cms_content_property_enum__sx_id', "cms_content_property_enum", ['sx_id'], true);

        $this->addColumn("cms_storage_file", "sx_id", $this->integer()->null());
        $this->createIndex('cms_storage_file__sx_id', "cms_storage_file", ['sx_id'], true);
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}