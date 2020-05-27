<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200527_101000__alter_table__cms_content_property extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_content_property";

        $this->addColumn($tableName, "cms_site_id", $this->integer());

        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}