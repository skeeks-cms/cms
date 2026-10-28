<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\Json;

/**
 * The filters of the access to clients in the company list (owner's decision
 * 29.10.2026): «Ответственные», «Защита», «Основной ответственный»,
 * «Сотрудник работает», «Дата создания». A saved view of the list keeps its
 * own visible filters, so they are appended to the views already saved; the
 * other settings of a view stay.
 */
class m261029_140000__update_backend_showing__company_access_filters extends Migration
{
    private const KEY = 'cms/admin-cms-company/index';
    private const WIDGET = 'skeeks\\cms\\backend\\widgets\\FiltersWidget';
    private const FILTERS = ['crm_responsible', 'crm_protection', 'crm_main_user', 'crm_employee', 'crm_created'];

    public function safeUp()
    {
        $this->change(static fn(array $visible) => array_values(array_unique(array_merge($visible, self::FILTERS))));
    }

    public function safeDown()
    {
        $this->change(static fn(array $visible) => array_values(array_diff($visible, self::FILTERS)));
    }

    private function change(callable $visibleFilters): void
    {
        if ($this->db->getTableSchema('{{%backend_showing}}', true) === null) {
            return;
        }
        foreach ((new Query())->from('{{%backend_showing}}')->andWhere(['key' => self::KEY])->all($this->db) as $row) {
            $config = $row['config_jsoned'] ? Json::decode($row['config_jsoned']) : [];
            if (!isset($config[self::WIDGET][self::KEY]['visibleFilters']) || !is_array($config[self::WIDGET][self::KEY]['visibleFilters'])) {
                continue;
            }
            $config[self::WIDGET][self::KEY]['visibleFilters'] = $visibleFilters($config[self::WIDGET][self::KEY]['visibleFilters']);
            $this->update('{{%backend_showing}}', ['config_jsoned' => Json::encode($config)], ['id' => (int)$row['id']]);
        }
    }
}
