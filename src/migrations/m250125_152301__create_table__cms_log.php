<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250125_152301__create_table__cms_log extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_log';
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

            'updated_by' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),

            'cms_company_id' => $this->integer()->null(),
            'cms_user_id' => $this->integer()->null(),

            'log_type' => $this->string(255)->notNull()->defaultValue("comment"),

            'comment' => $this->text()->null(),

            'model_code' => $this->string(255)->null(),
            'model_id' => $this->integer()->null(),
            'model_as_text' => $this->string(255)->null(),
            'data' => "LONGTEXT NULL",
            'sub_model_code' => $this->string(255)->null(),
            'sub_model_id' => $this->integer()->null(),
            'sub_model_log_type' => $this->string(255)->null(),
            'sub_model_as_text' => $this->string(255)->null(),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');

        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');

        $this->createIndex($tableName.'__log_type', $tableName, ['log_type']);

        $this->createIndex($tableName.'__cms_company_id', $tableName, ['cms_company_id']);
        $this->createIndex($tableName.'__cms_user_id', $tableName, ['cms_user_id']);

        $this->createIndex($tableName.'__model_code', $tableName, ['model_code']);
        $this->createIndex($tableName.'__model_id', $tableName, ['model_id']);
        $this->createIndex($tableName.'__sub_model_code', $tableName, ['sub_model_code']);
        $this->createIndex($tableName.'__sub_model_id', $tableName, ['sub_model_id']);
        $this->createIndex($tableName.'__sub_model_log_type', $tableName, ['sub_model_log_type']);
        $this->createIndex($tableName.'__model_as_text', $tableName, ['model_as_text']);
        $this->createIndex($tableName.'__sub_model_as_text', $tableName, ['sub_model_as_text']);

        $this->addCommentOnTable($tableName, 'Заметки компаний');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_company_id", $tableName,
            'cms_company_id', '{{%cms_company}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

