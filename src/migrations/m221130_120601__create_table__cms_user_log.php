<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m221130_120601__create_table__cms_user_log extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_user_log';
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
            'created_by_name' => $this->string(255),
            'created_at' => $this->integer(),

            'user_ip' => $this->string(20),

            'cms_site_id' => $this->integer()->notNull(),

            'model' => $this->string(255)->notNull(),
            'model_pk' => $this->string(255)->notNull(),

            'action_type'   => $this->string(255)->notNull(),

            'action_data' => $this->text(),

            'comment' => $this->text(),

        ], $tableOptions);


        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');


        $this->createIndex($tableName.'__cms_site_id', $tableName, 'cms_site_id');

        $this->createIndex($tableName.'__model', $tableName, ['model']);
        $this->createIndex($tableName.'__model_pk', $tableName, ['model_pk']);

        $this->createIndex($tableName.'__action_type', $tableName, ['action_type']);

        $this->addCommentOnTable($tableName, 'Действия пользователей');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );


        //Удаляя сайт - удаляются и все его телефоны
        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}