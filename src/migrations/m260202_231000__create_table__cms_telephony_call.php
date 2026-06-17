<?php

use yii\db\Migration;

class m260202_231000__create_table__cms_telephony_call extends Migration
{
    public function safeUp()
    {
        $this->createTable('cms_telephony_call', [
            'id' => $this->primaryKey(),

            'created_at' => $this->integer()->comment('Создано'),
            'updated_at' => $this->integer()->comment('Обновлено'),

            'cms_telephony_provider_id' => $this->integer()->comment('Провайдер телефонии'),

            // связи
            'cms_user_id' => $this->integer()->comment('Клиент (cms_user)'),
            'cms_company_id' => $this->integer()->comment('Компания'),

            'cms_worker_user_id' => $this->integer()->comment('Сотрудник, обработавший звонок'),

            // идентификатор
            'provider_call_id' => $this->string()->notNull()->comment('ID звонка у провайдера'),

            // данные звонка
            'direction' => $this->string(8)->notNull()->comment('in / out'),

            'status' => $this->string(32)->notNull()->comment('Статус (ringing, talking, missed, busy)'),
            'failed_reason' => $this->string(32)->null()->comment('Причина ошибки'),

            'duration' => $this->integer()->defaultValue(0)->comment('Длительность (сек)'),

            'started_at' => $this->integer()->comment('Начало звонка'),
            'ended_at' => $this->integer()->comment('Окончание звонка'),

            'client_phone' => $this->string(64)->notNull()->comment('Телефон клиента'),

            'provider_phone' => $this->string(64)->null()->comment('Телефон провайдера'),

            'provider_phone_from' => $this->string(64)->comment('От кого'),
            'provider_phone_to' => $this->string(64)->comment('Кому'),

            'provider_user_num' => $this->string(32)->comment('Внутренний номер сотрудника'),
            'provider_user_id' => $this->string(32)->comment('Внутренний id сотрудника'),

            'record_url' => $this->text()->comment('Ссылка на запись разговора'),

            'cms_record_file_id' => $this->integer()->null()->comment('Запись разговора'),

            'provider_data' => $this->text()->comment('Сырые данные провайдера'),

        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT="Телефонные звонки"');

        // indexes
        $this->createIndex(
            'cms_telephony_call__provider_phone',
            'cms_telephony_call',
            'provider_phone'
        );
        $this->createIndex(
            'cms_telephony_call__client_phone',
            'cms_telephony_call',
            'client_phone'
        );
        $this->createIndex(
            'cms_telephony_call__provider_user_num',
            'cms_telephony_call',
            'provider_user_num'
        );
        $this->createIndex(
            'cms_telephony_call__provider_user_id',
            'cms_telephony_call',
            'provider_user_id'
        );
        $this->createIndex(
            'cms_telephony_call__provider',
            'cms_telephony_call',
            'cms_telephony_provider_id'
        );
        $this->createIndex(
            'cms_telephony_call__cms_record_file_id',
            'cms_telephony_call',
            'cms_record_file_id'
        );

        $this->createIndex(
            'cms_telephony_call__cms_user_id',
            'cms_telephony_call',
            'cms_user_id'
        );

        $this->createIndex(
            'cms_telephony_call__cms_company_id',
            'cms_telephony_call',
            'cms_company_id'
        );

        $this->createIndex(
            'cms_telephony_call__cms_worker_user_id',
            'cms_telephony_call',
            'cms_worker_user_id'
        );

        $this->createIndex(
            'cms_telephony_call__status',
            'cms_telephony_call',
            'status'
        );

        $this->createIndex(
            'cms_telephony_call__provider_call_id',
            'cms_telephony_call',
            'provider_call_id'
        );


        // foreign keys
        $this->addForeignKey(
            'cms_telephony_call__provider',
            'cms_telephony_call',
            'cms_telephony_provider_id',
            'cms_telephony_provider',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'cms_telephony_call__cms_user_id',
            'cms_telephony_call',
            'cms_user_id',
            'cms_user',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'cms_telephony_call__cms_company_id',
            'cms_telephony_call',
            'cms_company_id',
            'cms_company',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'cms_telephony_call__cms_worker_user_id',
            'cms_telephony_call',
            'cms_worker_user_id',
            'cms_user',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            "cms_telephony_call__cms_record_file_id", "cms_telephony_call",
            'cms_record_file_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );

    }

    public function safeDown()
    {
        $this->dropTable('cms_telephony_call');
    }
}
