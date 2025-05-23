<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240717_162301__create_table__cms_company_link extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_company_link';
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


            'cms_company_id' => $this->integer()->notNull(),

            'link_type' => $this->string(255)->notNull(),
            'url' => $this->string(255)->notNull(),
            'name' => $this->string(),

            'sort' => $this->integer()->notNull()->defaultValue(500),


        ], $tableOptions);

        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');
        $this->createIndex($tableName.'__link_type', $tableName, 'link_type');

        $this->createIndex($tableName.'__cms_company_id', $tableName, ['cms_company_id']);

        $this->createIndex($tableName.'__uniq2company', $tableName, ['cms_company_id', 'url'], true);

        $this->createIndex($tableName.'__sort', $tableName, ['sort']);

        $this->addCommentOnTable($tableName, 'Ссылки компаний');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );


        //Удаляя компанию - удаляются и все email
        $this->addForeignKey(
            "{$tableName}__cms_company_id", $tableName,
            'cms_company_id', '{{%cms_company}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}