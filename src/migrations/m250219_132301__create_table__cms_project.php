<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250219_132301__create_table__cms_project extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_project';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [

            'id' => $this->primaryKey()->notNull(),
            'created_by' => $this->integer(11),
            'created_at' => $this->integer(11),
            'name' => $this->string(255)->notNull()->comment("Название"),
            'description' => $this->text()->comment("Описание проекта"),
            'is_active' => $this->boolean()->notNull()->defaultValue(1)->comment("Активность"),
            'is_private' => $this->boolean()->notNull()->defaultValue(1)->comment("Закрытый?"),
            'cms_image_id' => $this->integer()->comment("Фото"),

            'cms_company_id' => $this->integer()->comment("Компания"),
            'cms_user_id' => $this->integer()->comment("Клиент"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__name', $tableName, 'name');
        $this->createIndex($tableName.'__cms_image_id', $tableName, 'cms_image_id');
        $this->createIndex($tableName.'__is_active', $tableName, 'is_active');
        $this->createIndex($tableName.'__is_private', $tableName, 'is_private');
        $this->createIndex($tableName.'__cms_company_id', $tableName, 'cms_company_id');
        $this->createIndex($tableName.'__cms_user_id', $tableName, 'cms_user_id');

        $this->addCommentOnTable($tableName, 'Проекты');

        $this->addForeignKey(
            "{$tableName}__cms_image_id", $tableName,
            'cms_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_company_id", $tableName,
            'cms_company_id', '{{%cms_company}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}