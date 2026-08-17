<?php

use yii\db\Migration;
use yii\db\Query;

class m260817_000000__seed_default_cms_document_template extends Migration
{
    private const TEMPLATE_NAME = 'Стандартный шаблон';

    public function safeUp()
    {
        if (!$this->db->getTableSchema('{{%cms_document_template}}', true)) {
            throw new RuntimeException('Таблица cms_document_template не создана. Сначала выполните предыдущую миграцию.');
        }

        $siteIds = (new Query())
            ->select('id')
            ->from('{{%cms_site}}')
            ->orderBy(['id' => SORT_ASC])
            ->column($this->db);

        $timestamp = time();
        foreach ($siteIds as $siteId) {
            $hasDefault = (new Query())
                ->from('{{%cms_document_template}}')
                ->where([
                    'cms_site_id'  => (int)$siteId,
                    'document_type' => 'all',
                    'is_active'     => 1,
                    'is_default'    => 1,
                ])
                ->exists($this->db);

            if ($hasDefault) {
                continue;
            }

            $nameExists = (new Query())
                ->from('{{%cms_document_template}}')
                ->where([
                    'cms_site_id' => (int)$siteId,
                    'name'        => self::TEMPLATE_NAME,
                ])
                ->exists($this->db);

            if ($nameExists) {
                continue;
            }

            $this->insert('{{%cms_document_template}}', [
                'created_at'        => $timestamp,
                'updated_at'        => $timestamp,
                'cms_site_id'       => (int)$siteId,
                'name'              => self::TEMPLATE_NAME,
                'theme'             => 'skeeks-dark',
                'document_type'     => 'all',
                'is_active'         => 1,
                'is_default'        => 1,
                'show_cover'        => 1,
                'show_footer'       => 1,
                'show_page_numbers' => 1,
                'page_orientation'  => 'portrait',
            ]);
        }

        return true;
    }

    public function safeDown()
    {
        if (!$this->db->getTableSchema('{{%cms_document_template}}', true)) {
            return true;
        }

        $this->delete('{{%cms_document_template}}', [
            'name'          => self::TEMPLATE_NAME,
            'theme'         => 'skeeks-dark',
            'document_type' => 'all',
        ]);

        return true;
    }
}
