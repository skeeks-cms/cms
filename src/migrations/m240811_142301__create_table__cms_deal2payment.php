<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240811_142301__create_table__cms_deal2payment extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_deal2payment';
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

            'cms_deal_id' => $this->integer()->notNull()->comment("Сделка"),
            'shop_payment_id' => $this->integer()->notNull()->comment("Платеж"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');

        $this->createIndex($tableName.'__unique', $tableName, ['cms_deal_id', 'shop_payment_id'], true);

        $this->addCommentOnTable($tableName, 'Связь сделок с платежами');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_deal_id", $tableName,
            'cms_deal_id', '{{%cms_deal}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__shop_payment_id", $tableName,
            'shop_payment_id', '{{%shop_payment}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

