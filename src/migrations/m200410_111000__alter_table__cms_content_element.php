<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200410_111000__alter_table__cms_content_element extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_element";

        $this->addColumn($tableName, "external_id", $this->string(255)->comment("Идентификатор внешней системы"));
        $this->addColumn($tableName, "cms_site_id", $this->integer());

        $this->createIndex("external_id", $tableName, "external_id");
        $this->createIndex("cms_site_id", $tableName, "cms_site_id");

        $this->createIndex("external_id_unique", $tableName, ["cms_site_id", "external_id"], true);

        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', "cms_site", 'id', 'RESTRICT', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200410_111000__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}