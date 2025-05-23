<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240530_132301__create_table__cms_company extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_company';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [

            'id' => $this->primaryKey(),

            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),

            'name' => $this->string(255)->notNull()->comment("Название"),
            'cms_image_id' => $this->integer(11)->null()->comment("Изображение"),
            'description' => $this->text()->null()->comment("Описание"),

            'company_type' => $this->string(255)->notNull()->defaultValue("client")->comment("Тип клиент/поставщик"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__name', $tableName, 'name');
        $this->createIndex($tableName.'__cms_image_id', $tableName, 'cms_image_id');
        $this->createIndex($tableName.'__company_type', $tableName, 'company_type');

        $this->addCommentOnTable($tableName, 'Компании');

        $this->addForeignKey(
            "{$tableName}__cms_image_id", $tableName,
            'cms_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}