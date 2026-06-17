<?php

use yii\db\Migration;

class m260202_230100__create_table__cms_telephony_provider extends Migration
{
    public function safeUp()
    {
        $this->createTable('cms_telephony_provider', [
            'id' => $this->primaryKey(),

            'created_by' => $this->integer()->comment('Кто создал'),
            'updated_by' => $this->integer()->comment('Кто обновил'),
            'created_at' => $this->integer()->comment('Создано'),
            'updated_at' => $this->integer()->comment('Обновлено'),

            'name' => $this->string()->notNull()->comment('Название провайдера'),

            'priority' => $this->integer()->notNull()->defaultValue(100)->comment('Сортировка'),
            'is_active' => $this->integer(1)->defaultValue(1)->comment('Вкллючен?'),

            'component' => $this->string()->notNull()->comment('Yii2 класс обработчика'),
            'component_config' => $this->text()->comment('Настройки компонента (json / serialized)'),

        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT="Telephony провайдеры"');


        $this->createIndex('cms_telephony_provider__is_active', 'cms_telephony_provider', 'is_active');
        $this->createIndex('cms_telephony_provider__priority', 'cms_telephony_provider', 'priority');
        $this->createIndex('cms_telephony_provider__name', 'cms_telephony_provider', 'name', true);

        $this->addForeignKey(
            'cms_telephony_provider__created_by',
            'cms_telephony_provider',
            'created_by',
            'cms_user',
            'id',
            'SET NULL',
            'SET NULL'
        );

        $this->addForeignKey(
            'cms_telephony_provider__updated_by',
            'cms_telephony_provider',
            'updated_by',
            'cms_user',
            'id',
            'SET NULL',
            'SET NULL'
        );
    }

    public function safeDown()
    {
        $this->dropTable('cms_telephony_provider');
    }
}
