<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m231225_132301__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content";

        $this->dropForeignKey("cms_content_cms_content_type", $tableName);

        $this->alterColumn($tableName, "content_type", $this->string(32)->defaultValue(null)->null());

        $this->addForeignKey(
            "{$tableName}__content_type", $tableName,
            'content_type', '{{%cms_content_type}}', 'code', 'SET NULL', 'SET NULL'
        );

    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}