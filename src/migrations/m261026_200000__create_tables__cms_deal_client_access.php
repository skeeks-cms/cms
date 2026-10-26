<?php

use yii\db\Migration;

/**
 * "Сделка как услуга", step У1: client access to deals.
 *
 * - cms_deal_role (+ _area): ready-made roles of client people, editable;
 * - cms_client_access (+ _area): a person's grant on one deal or on all deals
 *   of a company (cms_deal_id is null), areas and levels copied from the role;
 * - cms_deal_type_area / cms_deal_area: what is open to the client;
 * - cms_deal_item: the composition of a deal.
 *
 * Existing access is carried over here, so no client loses access between the
 * code update and any later step: is_root = 1 becomes a company-wide grant with
 * the full role, the contact person of a company deal gets a full grant in that
 * deal. The owner of a deal without a company needs no row (computed).
 */
class m261026_200000__create_tables__cms_deal_client_access extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $audit = function () {
            return [
                'id'         => $this->primaryKey(),
                'created_by' => $this->integer()->null(),
                'updated_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ];
        };
        $auditKeys = function ($table, $prefix) {
            foreach (['created_by', 'updated_by'] as $column) {
                $this->addForeignKey($prefix.'__'.$column, $table, $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
            }
        };

        //Roles
        $this->createTable('{{%cms_deal_role}}', array_merge($audit(), [
            'name'        => $this->string(255)->notNull(),
            'code'        => $this->string(64)->null(),
            'description' => $this->text()->null(),
            'is_full'     => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'is_active'   => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'priority'    => $this->integer()->notNull()->defaultValue(100),
        ]), $tableOptions);
        $this->createIndex('cms_deal_role__name', '{{%cms_deal_role}}', 'name', true);
        $this->createIndex('cms_deal_role__code', '{{%cms_deal_role}}', 'code', true);
        $auditKeys('{{%cms_deal_role}}', 'cms_deal_role');
        $this->addCommentOnTable('{{%cms_deal_role}}', 'Роли доступа клиентов к услугам');

        $this->createTable('{{%cms_deal_role_area}}', [
            'id'               => $this->primaryKey(),
            'cms_deal_role_id' => $this->integer()->notNull(),
            'area'             => $this->string(64)->notNull(),
            'level'            => $this->smallInteger()->notNull()->defaultValue(0),
        ], $tableOptions);
        $this->createIndex('cms_deal_role_area__uniq', '{{%cms_deal_role_area}}', ['cms_deal_role_id', 'area'], true);
        $this->addForeignKey('cms_deal_role_area__cms_deal_role_id', '{{%cms_deal_role_area}}', 'cms_deal_role_id', '{{%cms_deal_role}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%cms_deal_role_area}}', 'Области доступа роли');

        //Grants
        $this->createTable('{{%cms_client_access}}', array_merge($audit(), [
            'cms_user_id'      => $this->integer()->notNull()->comment('Человек клиента'),
            'cms_company_id'   => $this->integer()->null()->comment('Компания'),
            'cms_deal_id'      => $this->integer()->null()->comment('Сделка; пусто — все услуги компании'),
            'scope_key'        => $this->string(32)->notNull()->comment('c:<company> или d:<deal>'),
            'cms_deal_role_id' => $this->integer()->null()->comment('Роль'),
            'is_full'          => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'is_custom'        => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Настроено вручную'),
            'comment'          => $this->string(255)->null(),
        ]), $tableOptions);
        $this->createIndex('cms_client_access__uniq', '{{%cms_client_access}}', ['cms_user_id', 'scope_key'], true);
        $this->createIndex('cms_client_access__cms_company_id', '{{%cms_client_access}}', 'cms_company_id');
        $this->createIndex('cms_client_access__cms_deal_id', '{{%cms_client_access}}', 'cms_deal_id');
        $this->createIndex('cms_client_access__cms_deal_role_id', '{{%cms_client_access}}', 'cms_deal_role_id');
        $auditKeys('{{%cms_client_access}}', 'cms_client_access');
        $this->addForeignKey('cms_client_access__cms_user_id', '{{%cms_client_access}}', 'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cms_client_access__cms_company_id', '{{%cms_client_access}}', 'cms_company_id', '{{%cms_company}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cms_client_access__cms_deal_id', '{{%cms_client_access}}', 'cms_deal_id', '{{%cms_deal}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cms_client_access__cms_deal_role_id', '{{%cms_client_access}}', 'cms_deal_role_id', '{{%cms_deal_role}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addCommentOnTable('{{%cms_client_access}}', 'Доступ людей клиента к услугам (сделкам)');

        $this->createTable('{{%cms_client_access_area}}', [
            'id'                   => $this->primaryKey(),
            'cms_client_access_id' => $this->integer()->notNull(),
            'area'                 => $this->string(64)->notNull(),
            'level'                => $this->smallInteger()->notNull()->defaultValue(0),
        ], $tableOptions);
        $this->createIndex('cms_client_access_area__uniq', '{{%cms_client_access_area}}', ['cms_client_access_id', 'area'], true);
        $this->addForeignKey('cms_client_access_area__access', '{{%cms_client_access_area}}', 'cms_client_access_id', '{{%cms_client_access}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%cms_client_access_area}}', 'Области доступа человека клиента');

        //What is open to the client
        $this->createTable('{{%cms_deal_type_area}}', [
            'id'               => $this->primaryKey(),
            'cms_deal_type_id' => $this->integer()->notNull(),
            'area'             => $this->string(64)->notNull(),
            'level'            => $this->smallInteger()->notNull()->defaultValue(0),
        ], $tableOptions);
        $this->createIndex('cms_deal_type_area__uniq', '{{%cms_deal_type_area}}', ['cms_deal_type_id', 'area'], true);
        $this->addForeignKey('cms_deal_type_area__cms_deal_type_id', '{{%cms_deal_type_area}}', 'cms_deal_type_id', '{{%cms_deal_type}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%cms_deal_type_area}}', 'Области доступа, которые тип сделки может открыть клиенту; нет строк — все');

        $this->createTable('{{%cms_deal_area}}', [
            'id'          => $this->primaryKey(),
            'cms_deal_id' => $this->integer()->notNull(),
            'area'        => $this->string(64)->notNull(),
            'level'       => $this->smallInteger()->notNull()->defaultValue(0),
        ], $tableOptions);
        $this->createIndex('cms_deal_area__uniq', '{{%cms_deal_area}}', ['cms_deal_id', 'area'], true);
        $this->addForeignKey('cms_deal_area__cms_deal_id', '{{%cms_deal_area}}', 'cms_deal_id', '{{%cms_deal}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%cms_deal_area}}', 'Ограничения областей доступа в сделке; нет строки — как у типа');

        //Composition
        $this->createTable('{{%cms_deal_item}}', array_merge($audit(), [
            'cms_deal_id'   => $this->integer()->notNull(),
            'name'          => $this->string(255)->notNull(),
            'description'   => $this->text()->null(),
            'quantity'      => $this->decimal(18, 4)->notNull()->defaultValue(1),
            'price'         => $this->decimal(19, 4)->notNull()->defaultValue(0),
            'amount'        => $this->decimal(19, 4)->notNull()->defaultValue(0),
            'period'        => $this->string(10)->null()->comment('Пусто — как у сделки'),
            'resource_type' => $this->string(64)->null(),
            'resource_id'   => $this->integer()->null(),
            'priority'      => $this->integer()->notNull()->defaultValue(100),
        ]), $tableOptions);
        $this->createIndex('cms_deal_item__cms_deal_id', '{{%cms_deal_item}}', ['cms_deal_id', 'priority']);
        $this->createIndex('cms_deal_item__resource', '{{%cms_deal_item}}', ['resource_type', 'resource_id']);
        $auditKeys('{{%cms_deal_item}}', 'cms_deal_item');
        $this->addForeignKey('cms_deal_item__cms_deal_id', '{{%cms_deal_item}}', 'cms_deal_id', '{{%cms_deal}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%cms_deal_item}}', 'Состав сделки');

        $this->seedRoles();
        $this->carryOverAccess();
    }

    protected function seedRoles()
    {
        $now = time();
        $roles = [
            ['full', 'Директор (полный доступ)', 'Все области доступа, в том числе будущие', 1, 100, []],
            ['accountant', 'Бухгалтер', 'Документы, счета и оплата', 0, 200, ['documents' => 10, 'billing' => 10]],
            ['marketer', 'Маркетолог / менеджер', 'Задачи и отчёты', 0, 300, ['tasks' => 30, 'reports' => 10]],
            ['viewer', 'Только просмотр', 'Смотреть задачи и отчёты', 0, 400, ['tasks' => 10, 'reports' => 10]],
        ];
        foreach ($roles as [$code, $name, $description, $isFull, $priority, $areas]) {
            $this->insert('{{%cms_deal_role}}', [
                'code'        => $code,
                'name'        => $name,
                'description' => $description,
                'is_full'     => $isFull,
                'priority'    => $priority,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $roleId = (int)$this->db->getLastInsertID();
            foreach ($areas as $area => $level) {
                $this->insert('{{%cms_deal_role_area}}', ['cms_deal_role_id' => $roleId, 'area' => $area, 'level' => $level]);
            }
        }
    }

    protected function carryOverAccess()
    {
        $now = time();
        $fullRoleId = (int)(new \yii\db\Query())->select('id')->from('{{%cms_deal_role}}')->where(['code' => 'full'])->scalar($this->db);

        $rows = (new \yii\db\Query())
            ->select(['cms_user_id', 'cms_company_id'])
            ->from('{{%cms_company2user}}')
            ->where(['is_root' => 1])
            ->all($this->db);
        $insert = [];
        foreach ($rows as $row) {
            $insert[] = [(int)$row['cms_user_id'], (int)$row['cms_company_id'], null, 'c:'.(int)$row['cms_company_id'], $fullRoleId, 1, $now, $now];
        }

        //The contact of a company deal saw that deal before; keep it.
        $rows = (new \yii\db\Query())
            ->select(['id', 'cms_user_id', 'cms_company_id'])
            ->from('{{%cms_deal}}')
            ->where(['not', ['cms_company_id' => null]])
            ->andWhere(['not', ['cms_user_id' => null]])
            ->all($this->db);
        foreach ($rows as $row) {
            $insert[] = [(int)$row['cms_user_id'], (int)$row['cms_company_id'], (int)$row['id'], 'd:'.(int)$row['id'], $fullRoleId, 1, $now, $now];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            $this->batchInsert('{{%cms_client_access}}', [
                'cms_user_id', 'cms_company_id', 'cms_deal_id', 'scope_key', 'cms_deal_role_id', 'is_full', 'created_at', 'updated_at',
            ], $chunk);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_deal_item}}');
        $this->dropTable('{{%cms_deal_area}}');
        $this->dropTable('{{%cms_deal_type_area}}');
        $this->dropTable('{{%cms_client_access_area}}');
        $this->dropTable('{{%cms_client_access}}');
        $this->dropTable('{{%cms_deal_role_area}}');
        $this->dropTable('{{%cms_deal_role}}');
    }
}
