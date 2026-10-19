<?php

use yii\db\Migration;

/**
 * Addresses, links and requisites of a lead, mirroring the company card so a
 * manager can record them before a company exists and they move on conversion.
 */
class m261019_120000__create_tables__cms_lead_address_link_contractor extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%cms_lead_address}}', array_merge($this->baseColumns(), [
            'cms_lead_id'      => $this->integer()->notNull(),
            'name'             => $this->string(255)->null()->comment('Название адреса (необязательное)'),
            'value'            => $this->string(255)->notNull()->comment('Полный адрес'),
            'latitude'         => $this->double()->notNull()->comment('Широта'),
            'longitude'        => $this->double()->notNull()->comment('Долгота'),
            'entrance'         => $this->string(255)->null()->comment('Подъезд'),
            'floor'            => $this->string(255)->null()->comment('Этаж'),
            'apartment_number' => $this->string(255)->null()->comment('Номер квартиры'),
            'comment'          => $this->text()->null()->comment('Комментарий'),
            'postcode'         => $this->string(255)->null()->comment('Почтовый индекс'),
            'sort'             => $this->integer()->notNull()->defaultValue(500),
        ]), $tableOptions);
        $this->createIndex('cms_lead_address__uniq2lead', '{{%cms_lead_address}}', ['cms_lead_id', 'value'], true);
        $this->addLeadKeys('cms_lead_address');
        $this->addCommentOnTable('{{%cms_lead_address}}', 'Адреса лидов');

        $this->createTable('{{%cms_lead_link}}', array_merge($this->baseColumns(), [
            'cms_lead_id' => $this->integer()->notNull(),
            'link_type'   => $this->string(255)->notNull(),
            'url'         => $this->string(255)->notNull(),
            'name'        => $this->string(255)->null(),
            'sort'        => $this->integer()->notNull()->defaultValue(500),
        ]), $tableOptions);
        $this->createIndex('cms_lead_link__uniq2lead', '{{%cms_lead_link}}', ['cms_lead_id', 'url'], true);
        $this->addLeadKeys('cms_lead_link');
        $this->addCommentOnTable('{{%cms_lead_link}}', 'Ссылки лидов');

        $this->createTable('{{%cms_lead2contractor}}', array_merge($this->baseColumns(), [
            'cms_lead_id'       => $this->integer()->notNull()->comment('Лид'),
            'cms_contractor_id' => $this->integer()->notNull()->comment('Контрагент'),
        ]), $tableOptions);
        $this->createIndex('cms_lead2contractor__uniq', '{{%cms_lead2contractor}}', ['cms_lead_id', 'cms_contractor_id'], true);
        $this->createIndex('cms_lead2contractor__cms_contractor_id', '{{%cms_lead2contractor}}', 'cms_contractor_id');
        $this->addLeadKeys('cms_lead2contractor');
        $this->addForeignKey('cms_lead2contractor__cms_contractor_id', '{{%cms_lead2contractor}}', 'cms_contractor_id',
            '{{%cms_contractor}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%cms_lead2contractor}}', 'Реквизиты лидов: связь с контрагентами');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_lead2contractor}}');
        $this->dropTable('{{%cms_lead_link}}');
        $this->dropTable('{{%cms_lead_address}}');
    }

    private function baseColumns(): array
    {
        return [
            'id'         => $this->primaryKey(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ];
    }

    private function addLeadKeys(string $tableName): void
    {
        foreach (['created_by', 'updated_by', 'cms_lead_id'] as $column) {
            $this->createIndex($tableName.'__'.$column, '{{%'.$tableName.'}}', $column);
        }
        foreach (['created_by', 'updated_by'] as $column) {
            $this->addForeignKey($tableName.'__'.$column, '{{%'.$tableName.'}}', $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        }
        $this->addForeignKey($tableName.'__cms_lead_id', '{{%'.$tableName.'}}', 'cms_lead_id', '{{%cms_lead}}', 'id', 'CASCADE', 'CASCADE');
    }
}
