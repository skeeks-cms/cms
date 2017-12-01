<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m151023_153220_alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_content}}', 'default_tree_id', $this->integer()); //Главный раздел по умолчанию
        $this->addColumn('{{%cms_content}}', 'is_allow_change_tree', $this->string(1)->notNull()->defaultValue('Y')); //Разрешено ли менять главный раздел

        $this->addColumn('{{%cms_content}}', 'root_tree_id', $this->integer()); //Корневой раздел, от него будет строится дерево с подразделами к которым можно привязывать элементы.


        $this->createIndex('default_tree_id', '{{%cms_content}}', 'default_tree_id');
        $this->createIndex('is_allow_change_tree', '{{%cms_content}}', 'is_allow_change_tree');
        $this->createIndex('root_tree_id', '{{%cms_content}}', 'root_tree_id');

        $this->addForeignKey(
            'cms_content__default_tree_id', "{{%cms_content}}",
            'default_tree_id', '{{%cms_tree}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content__root_tree_id', "{{%cms_content}}",
            'root_tree_id', '{{%cms_tree}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m151023_153220_alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}