<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\Json;

/**
 * The client list (`admin-user`) like the company list (owner's decision
 * 29.10.2026, crm\CrmClientGrid): the column «Ответственный и сотрудники»
 * after the name and the filters of the access to clients. A saved view keeps
 * its own visible columns and filters, so they are added to the views already
 * saved: the column only to a view that shows the name (not to an export view
 * of one column), the filters to every view with its own filters; the old
 * filter «Ответственный» (crmResponsible) becomes «Ответственные». The other
 * settings of a view stay.
 */
class m261029_180000__update_backend_showing__client_access_columns extends Migration
{
    private const KEY = 'cms/admin-user/index';
    private const GRID = 'skeeks\\cms\\backend\\widgets\\GridViewWidget';
    private const FILTERS_WIDGET = 'skeeks\\cms\\backend\\widgets\\FiltersWidget';
    private const COLUMN = 'managers';
    private const FILTERS = ['crm_responsible', 'crm_protection', 'crm_main_user', 'crm_employee', 'crm_created'];

    public function safeUp()
    {
        $this->change(static function (array $columns) {
            if (in_array(self::COLUMN, $columns, true) || ($at = array_search('custom', $columns, true)) === false) {
                return $columns;
            }
            array_splice($columns, (int)$at + 1, 0, [self::COLUMN]);

            return $columns;
        }, static fn(array $filters) => array_values(array_unique(array_merge(array_diff($filters, ['crmResponsible']), self::FILTERS))));
    }

    public function safeDown()
    {
        $this->change(static fn(array $columns) => array_values(array_diff($columns, [self::COLUMN])),
            static fn(array $filters) => array_values(array_diff($filters, self::FILTERS)));
    }

    private function change(callable $visibleColumns, callable $visibleFilters): void
    {
        if ($this->db->getTableSchema('{{%backend_showing}}', true) === null) {
            return;
        }
        foreach ((new Query())->from('{{%backend_showing}}')->andWhere(['key' => self::KEY])->all($this->db) as $row) {
            $config = $row['config_jsoned'] ? Json::decode($row['config_jsoned']) : [];
            $before = $config;
            if (isset($config[self::GRID][self::KEY]['visibleColumns']) && is_array($config[self::GRID][self::KEY]['visibleColumns'])) {
                $config[self::GRID][self::KEY]['visibleColumns'] = $visibleColumns($config[self::GRID][self::KEY]['visibleColumns']);
            }
            if (isset($config[self::FILTERS_WIDGET][self::KEY]['visibleFilters']) && is_array($config[self::FILTERS_WIDGET][self::KEY]['visibleFilters'])) {
                $config[self::FILTERS_WIDGET][self::KEY]['visibleFilters'] = $visibleFilters($config[self::FILTERS_WIDGET][self::KEY]['visibleFilters']);
            }
            if ($config !== $before) {
                $this->update('{{%backend_showing}}', ['config_jsoned' => Json::encode($config)], ['id' => (int)$row['id']]);
            }
        }
    }
}
