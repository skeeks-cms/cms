<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250408_152301__create_table__cms_web_notify extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_web_notify';
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

            'created_at' => $this->integer()->null(),

            'name' => $this->string(255)->notNull(),
            'comment' => $this->text()->null(),

            'model_code' => $this->string(255)->null(),
            'model_id' => $this->integer()->null(),

            'cms_user_id' => $this->integer()->notNull(),

            'is_read' => $this->integer(1)->defaultValue(0)->notNull(),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');

        $this->createIndex($tableName.'__name', $tableName, 'name');
        $this->createIndex($tableName.'__is_read', $tableName, 'is_read');

        $this->createIndex($tableName.'__model_code', $tableName, ['model_code']);
        $this->createIndex($tableName.'__model_id', $tableName, ['model_id']);

        $this->createIndex($tableName.'__cms_user_id', $tableName, ['cms_user_id']);

        $this->addCommentOnTable($tableName, 'Уведомления пользователей');

        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

