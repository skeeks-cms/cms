<?php

use yii\db\Migration;

class m260202_230200__create_table__cms_telephony_user extends Migration
{
    public function safeUp()
    {
        $this->createTable('cms_telephony_user', [
            'id' => $this->primaryKey(),

            'created_at' => $this->integer()->comment('Создано'),
            'updated_at' => $this->integer()->comment('Обновлено'),

            'cms_worker_user_id' => $this->integer()->null()->comment('Сотрудник CRM'),

            'cms_telephony_provider_id' => $this->integer()->notNull()->comment('Провайдер телефонии'),

            'provider_user_num' => $this->string(32)->notNull()->comment('Внутренний номер (201)'),

            'sip_uri' => $this->string()->null()->comment('SIP логин'),
            'sip_password' => $this->string()->null()->comment('SIP пароль'),

            'ws_url' => $this->string()->comment('WebSocket URL (wss://...)'),
            'ice_servers' => $this->text()->comment('ICE / STUN / TURN серверы (JSON)'),

            'display_name' => $this->string()->comment('Отображаемое имя'),

            'is_active' => $this->integer(1)->notNull()->defaultValue(1)->comment('Ативность'),

        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT="SIP учетные данные пользователей"');

        $this->createIndex(
            'cms_telephony_user__uniq',
            'cms_telephony_user',
            ['provider_user_num', 'cms_telephony_provider_id'],
            true
        );

        //В одной телефонии один сотрудник в црм
        $this->createIndex(
            'cms_telephony_user__uniq2',
            'cms_telephony_user',
            ['cms_worker_user_id', 'cms_telephony_provider_id'],
            true
        );

        $this->createIndex('cms_telephony_user__provider_user_num', 'cms_telephony_user', 'provider_user_num');
        $this->createIndex('cms_telephony_user__cms_worker_user_id', 'cms_telephony_user', 'cms_worker_user_id');
        $this->createIndex('cms_telephony_user__provider', 'cms_telephony_user', 'cms_telephony_provider_id');
        $this->createIndex('cms_telephony_user__is_active', 'cms_telephony_user', 'is_active');

        $this->addForeignKey(
            'cms_telephony_user__cms_worker_user_id',
            'cms_telephony_user',
            'cms_worker_user_id',
            'cms_user',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'cms_telephony_user__provider',
            'cms_telephony_user',
            'cms_telephony_provider_id',
            'cms_telephony_provider',
            'id',
            'CASCADE',
            'CASCADE'
        );

    }

    public function safeDown()
    {
        $this->dropTable('cms_telephony_user');
    }
}
