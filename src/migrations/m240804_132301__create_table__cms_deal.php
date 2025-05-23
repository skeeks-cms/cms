<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240804_132301__create_table__cms_deal extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_deal';
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

            'start_at' => $this->integer()->notNull()->comment("Начало сделки"),
            'end_at' => $this->integer()->null()->comment("Дата завершения сделки"),

            'cms_deal_type_id' => $this->integer()->notNull()->comment("Тип сделки"),

            'cms_company_id' => $this->integer()->null()->comment("Связь с компанией"),
            'cms_user_id' => $this->integer()->null()->comment("Связь с пользователем"),

            'name' => $this->string(255)->notNull()->comment("Название"),
            'description' => $this->text()->notNull()->comment("Описание"),

            'amount' => $this->decimal(19,4)->notNull()->defaultValue(0.0000)->comment("Значение цены"),
            'currency_code' => $this->string(3)->notNull()->defaultValue('RUB')->comment("Валюта"),

            'is_periodic' => $this->boolean()->unsigned()->notNull()->comment("Периодическая или разовая сделка?"),
            'period' => $this->string(10)->null()->comment("Период действия для периодического сделки"),

            'is_active' => $this->boolean()->notNull()->defaultValue(1)->comment("Активность"),
            'is_auto' => $this->boolean()->notNull()->defaultValue(1)->comment("Авто продление + уведомления"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__start_at', $tableName, 'start_at');
        $this->createIndex($tableName.'__end_at', $tableName, 'end_at');
        $this->createIndex($tableName.'__is_active', $tableName, 'is_active');
        $this->createIndex($tableName.'__is_periodic', $tableName, 'is_periodic');
        $this->createIndex($tableName.'__is_auto', $tableName, 'is_auto');
        $this->createIndex($tableName.'__amount', $tableName, 'amount');
        $this->createIndex($tableName.'__currency_code', $tableName, 'currency_code');
        $this->createIndex($tableName.'__name', $tableName, 'name');
        $this->createIndex($tableName.'__cms_company_id', $tableName, 'cms_company_id');
        $this->createIndex($tableName.'__cms_user_id', $tableName, 'cms_user_id');
        $this->createIndex($tableName.'__cms_deal_type_id', $tableName, 'cms_deal_type_id');

        $this->addCommentOnTable($tableName, 'Сделки');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__currency_code", $tableName,
            'currency_code', '{{%money_currency}}', 'code', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            "{$tableName}__cms_company_id", $tableName,
            'cms_company_id', '{{%cms_company}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            "{$tableName}__cms_deal_type_id", $tableName,
            'cms_deal_type_id', '{{%cms_deal_type}}', 'id', 'RESTRICT', 'RESTRICT'
        );

    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

