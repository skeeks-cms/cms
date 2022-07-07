<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220705_120601__create_table__cms_callcheck_message extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_callcheck_message';
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

            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),

            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),

            'user_ip' => $this->string(20),

            'cms_site_id' => $this->integer()->notNull(),

            'cms_callcheck_provider_id' => $this->integer(),

            'phone'   => $this->string()->notNull(),
            'status' => $this->string(255)->notNull(),
            'code' => $this->string(255),

            'error_message' => $this->string(255),

            'provider_status' => $this->string(255),
            'provider_call_id' => $this->string(255),
            'provider_response_data' => $this->text(),

        ], $tableOptions);


        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');


        $this->createIndex($tableName.'__cms_site_id', $tableName, 'cms_site_id');
        $this->createIndex($tableName.'__status', $tableName, ['status']);
        $this->createIndex($tableName.'__code', $tableName, ['code']);

        $this->createIndex($tableName.'__provider_status', $tableName, ['provider_status']);
        $this->createIndex($tableName.'__provider_call_id', $tableName, ['provider_call_id']);

        $this->addCommentOnTable($tableName, 'Авторзиация по звонку сообщения');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        //Удаляя сайт - удаляются и все его телефоны
        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_callcheck_provider_id", $tableName,
            'cms_callcheck_provider_id', '{{%cms_callcheck_provider}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}