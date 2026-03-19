<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m260319_163221_alter_table__cms_content_element extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_content_element';

        $this->addColumn($tableName, "cms_content_model_id", $this->integer()->null()->comment("Связь с моделью"));

        $this->createIndex("{$tableName}__cms_content_model_id", $tableName, "cms_content_model_id");

        $this->addForeignKey(
            "{$tableName}__cms_content_model_id", $tableName,
            'cms_content_model_id', '{{%cms_content_model}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m240411_142301__alter_table__shop_store cannot be reverted.\n";
        return false;
    }
}