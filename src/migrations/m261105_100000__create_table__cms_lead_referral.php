<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;
use yii\db\Query;

/**
 * «Откуда узнали» (owner's decision 05.11.2026): a site list of where clients
 * learned about the company, and the deal field that points to it.
 *
 * - `cms_lead_referral`: name, optional code, order, activity and matching
 *   rules (crm\LeadReferrals); the client keeps the list himself.
 * - `cms_lead.cms_lead_referral_id`, `cms_lead.referral_note` (why the value
 *   was filled automatically; empty when a person chose it).
 * - Every existing site gets the starting list (missing codes only, so a
 *   repeated or interrupted run adds nothing twice and never overwrites what
 *   users changed).
 *
 * Rollback is refused while deals have the field set: the site restores its
 * backup instead.
 */
class m261105_100000__create_table__cms_lead_referral extends Migration
{
    use RepeatableMigrationTrait;

    /** The starting list; the same as crm\LeadReferrals::defaults() on the day of the migration. */
    private const DEFAULTS = [
        ['yandex_direct', 'Яндекс Директ', 10, "utm_source=yandex,yandex_direct,direct utm_medium=cpc,cpm,cpa,paid\nyclid=*"],
        ['seo', 'Поиск (SEO)', 20, "utm_medium=organic\nreferrer=yandex.ru,ya.ru,yandex.com,yandex.by,yandex.kz,google.com,google.ru,bing.com,go.mail.ru,duckduckgo.com,search.yahoo.com,nova.rambler.ru"],
        ['yandex_maps', 'Яндекс Карты', 30, "utm_source=yandex_maps,yandexmaps,ymaps\nreferrer=yandex.ru/maps,yandex.com/maps,yandex.by/maps,maps.yandex.ru,yandex.ru/profile"],
        ['2gis', '2ГИС', 40, "utm_source=2gis\nreferrer=2gis.ru,2gis.com,2gis.kz"],
        ['avito', 'Авито', 50, "utm_source=avito\nreferrer=avito.ru"],
        ['social', 'Соцсети', 60, "utm_source=vk,vkontakte,telegram,tg,ok,odnoklassniki,instagram,facebook,dzen\nreferrer=vk.com,vk.ru,ok.ru,t.me,instagram.com,facebook.com,dzen.ru"],
        ['signboard', 'Вывеска / проходил мимо', 70, null],
        ['recommendation', 'Рекомендация', 80, null],
        ['repeat', 'Повторный клиент', 90, null],
        ['mailing', 'Рассылка', 100, "utm_medium=email\nchannel=mailing"],
        ['other', 'Другое', 110, null],
    ];

    public function safeUp()
    {
        $table = '{{%cms_lead_referral}}';
        $tableOptions = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;

        $this->createTableIfMissing($table, [
            'id' => $this->primaryKey(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'cms_site_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'code' => $this->string(64)->null()->comment('Для правил и API'),
            'sort' => $this->integer()->notNull()->defaultValue(100),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
            'match_rules' => $this->text()->null()->comment('Правила сопоставления: строка — правило'),
        ], $tableOptions);
        $this->createIndexIfMissing('cms_lead_referral__site_sort', $table, ['cms_site_id', 'sort']);
        $this->createIndexIfMissing('cms_lead_referral__site_name', $table, ['cms_site_id', 'name'], true);
        $this->createIndexIfMissing('cms_lead_referral__site_code', $table, ['cms_site_id', 'code'], true);
        foreach (['created_by', 'updated_by'] as $column) {
            $this->addForeignKeyIfMissing('cms_lead_referral__'.$column, $table, $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        }
        $this->addForeignKeyIfMissing('cms_lead_referral__cms_site_id', $table, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');

        $this->addColumnIfMissing('{{%cms_lead}}', 'cms_lead_referral_id', $this->integer()->null()->comment('Откуда узнали'));
        $this->addColumnIfMissing('{{%cms_lead}}', 'referral_note', $this->string(255)->null()->comment('Как определено «Откуда узнали»'));
        $this->createIndexIfMissing('cms_lead__cms_lead_referral_id', '{{%cms_lead}}', 'cms_lead_referral_id');
        // A used item is refused by the model; SET NULL is the last safety net.
        $this->addForeignKeyIfMissing('cms_lead__cms_lead_referral_id', '{{%cms_lead}}', 'cms_lead_referral_id', $table, 'id', 'SET NULL', 'CASCADE');

        // The starting list for every existing site: only codes the site does not have yet.
        $now = time();
        foreach ((new Query())->select('id')->from('{{%cms_site}}')->column($this->db) as $siteId) {
            foreach (self::DEFAULTS as [$code, $name, $sort, $rules]) {
                $exists = (new Query())->from($table)->where(['cms_site_id' => (int)$siteId])
                    ->andWhere(['or', ['code' => $code], ['name' => $name]])->exists($this->db);
                if ($exists) {
                    continue;
                }
                $this->insert($table, [
                    'cms_site_id' => (int)$siteId,
                    'name' => $name,
                    'code' => $code,
                    'sort' => $sort,
                    'is_active' => 1,
                    'match_rules' => $rules,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return true;
    }

    public function safeDown()
    {
        // The starting list has no author; an item a person added or changed has one.
        $this->refuseRollbackWithData([
            'сделки с «Откуда узнали»' => $this->hasColumn('{{%cms_lead}}', 'cms_lead_referral_id')
                ? (new Query())->from('{{%cms_lead}}')->where(['not', ['cms_lead_referral_id' => null]])
                : null,
            'пункты «Откуда узнали», добавленные или изменённые людьми' => $this->hasTable('{{%cms_lead_referral}}')
                ? (new Query())->from('{{%cms_lead_referral}}')->where(['or', ['not', ['created_by' => null]], ['not', ['updated_by' => null]]])
                : null,
        ]);

        $this->dropForeignKeyIfExists('cms_lead__cms_lead_referral_id', '{{%cms_lead}}');
        $this->dropIndexIfExists('cms_lead__cms_lead_referral_id', '{{%cms_lead}}');
        $this->dropColumnIfExists('{{%cms_lead}}', 'referral_note');
        $this->dropColumnIfExists('{{%cms_lead}}', 'cms_lead_referral_id');
        $this->dropTableIfExists('{{%cms_lead_referral}}');

        return true;
    }
}
