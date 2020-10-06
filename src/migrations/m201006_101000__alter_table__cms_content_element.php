<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m201006_101000__alter_table__cms_content_element extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_content_element";

        $this->dropIndex("external_id_unique", $tableName);
        $this->createIndex("external_id_unique", $tableName, ['cms_site_id', 'external_id', 'content_id'], true);
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}