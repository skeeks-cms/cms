<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m201127_121000__alter_table__cms_content_element extends Migration
{

    public function safeUp()
    {
        $tableName = 'cms_content_element';
        $this->addColumn($tableName, "main_cce_id", $this->integer()->comment("Элемент берет информацию с другого элемента"));

        $this->createIndex("main_cce_id", $tableName, ['main_cce_id']);

        $this->addForeignKey(
            "{$tableName}__main_cce_id", $tableName,
            'main_cce_id', $tableName, 'id', 'SET NULL', 'SET NULL'
        );

        $this->addColumn($tableName, "main_cce_at", $this->integer()->comment("Когда создана привязка"));
        $this->addColumn($tableName, "main_cce_by", $this->integer(1)->comment("Кем создана привязка"));

        $this->createIndex("main_cce_at", $tableName, ["main_cce_at"]);
        $this->createIndex("main_cce_by", $tableName, ["main_cce_by"]);

        $this->addForeignKey(
            "{$tableName}__main_cce_by", $tableName,
            'main_cce_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}