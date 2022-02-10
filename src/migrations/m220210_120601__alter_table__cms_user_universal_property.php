<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220210_120601__alter_table__cms_user_universal_property extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_user_universal_property";

        $this->addColumn($tableName, "cms_site_id", $this->integer());

        $this->createIndex("cms_site_id", $tableName, ['cms_site_id']);

        $subQuery = $this->db->createCommand("
            UPDATE 
                `{$tableName}` as c
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
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}