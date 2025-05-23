<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240808_132301__create_table__cms_contractor_bank extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_contractor_bank';
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

            'cms_contractor_id' => $this->integer()->notNull()->comment("Контрагент"),

            'bank_name' => $this->string(255)->notNull()->comment("Банк"),
            'bic' => $this->string(12)->notNull()->comment("БИК"),
            'checking_account' => $this->string(20)->notNull()->comment("Расчетный счет"),

            'correspondent_account' => $this->string(20)->null()->comment("Корреспондентский счёт"),
            'bank_address' => $this->string(255)->null()->comment("Адрес банка"),
            'comment' => $this->text()->null()->comment("Комментарий"),

            'is_active' => $this->integer(1)->notNull()->defaultValue(1)->comment("Активность"),
            'sort' => $this->integer()->notNull()->defaultValue(100)->comment("Сортировка"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__bank_name', $tableName, 'bank_name');
        $this->createIndex($tableName.'__cms_contractor_id', $tableName, 'cms_contractor_id');
        $this->createIndex($tableName.'__bic', $tableName, 'bic');
        $this->createIndex($tableName.'__checking_account', $tableName, 'checking_account');
        $this->createIndex($tableName.'__is_active', $tableName, 'is_active');
        $this->createIndex($tableName.'__sort', $tableName, 'sort');

        $this->createIndex($tableName.'__unique', $tableName, ['cms_contractor_id', 'bic', 'checking_account'], true);

        $this->addCommentOnTable($tableName, 'Банковские реквизиты');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_contractor_id", $tableName,
            'cms_contractor_id', '{{%cms_contractor}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

