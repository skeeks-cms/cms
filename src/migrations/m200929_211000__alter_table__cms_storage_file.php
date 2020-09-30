<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200929_211000__alter_table__cms_storage_file extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_storage_file";

        $this->addColumn($tableName, "cms_site_id", $this->integer());

        $this->createIndex("cms_site_id", $tableName, ['cms_site_id']);

        $subQuery = $this->db->createCommand("
            UPDATE 
                `cms_storage_file` as c
            SET 
                c.cms_site_id = (select cms_site.id from cms_site where cms_site.is_default = 1)
        ")->execute();

        $this->alterColumn($tableName, "cms_site_id", $this->integer()->notNull());

        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}