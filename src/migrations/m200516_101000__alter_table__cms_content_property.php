<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200516_101000__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_property";
        $this->dropForeignKey("cms_content_property_content_id", $tableName);
        $this->dropColumn($tableName, "content_id");
    }

    public function safeDown()
    {
        echo "m200410_121000__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}