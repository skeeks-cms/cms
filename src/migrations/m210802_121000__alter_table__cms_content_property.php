<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m210802_121000__alter_table__cms_content_property extends Migration
{

    public function safeUp()
    {
        $tableName = 'cms_content_property';

        $this->db->createCommand("UPDATE 
            `cms_content_property` as ccp 
            LEFT JOIN (
                SELECT cs.id
                FROM cms_site as cs
                WHERE cs.is_default = 1
            ) site ON 1 = 1 
        SET 
            ccp.`cms_site_id` = site.id
        WHERE 
            ccp.`cms_site_id` IS NULL
        ")->execute();

        $this->dropIndex("code_2", $tableName);

        $this->createIndex($tableName.'__code2site', $tableName, ['code', 'cms_site_id'], true);
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}