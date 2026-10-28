<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\Json;

/**
 * The saved tabs of «Доступы» (AdminCmsCrmAccessLogController, the journal of
 * the access to clients): «Ждут моего решения» — the default one the menu
 * opens, «Все». Public tabs of every site; a site that already has the tabs
 * keeps them.
 */
class m261029_150000__insert_backend_showing__crm_access_log extends Migration
{
    private const KEY = 'cms/admin-cms-crm-access-log/index';
    private const WIDGET = 'skeeks\\cms\\backend\\widgets\\FiltersWidget';
    private const FILTERS = ['scope', 'event', 'employee', 'company', 'period'];

    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%backend_showing}}', true) === null) {
            return;
        }
        $now = time();
        foreach ((new Query())->select('id')->from('{{%cms_site}}')->column($this->db) as $siteId) {
            if ((new Query())->from('{{%backend_showing}}')->andWhere(['key' => self::KEY, 'cms_site_id' => (int)$siteId])->exists($this->db)) {
                continue;
            }
            foreach ([['Ждут моего решения', 1, 10, 'waiting'], ['Все', 0, 20, 'all']] as [$name, $isDefault, $priority, $scope]) {
                $this->insert('{{%backend_showing}}', [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'cms_user_id' => null,
                    'cms_site_id' => (int)$siteId,
                    'is_default' => $isDefault,
                    'name' => $name,
                    'key' => self::KEY,
                    'priority' => $priority,
                    'config_jsoned' => Json::encode([self::WIDGET => [self::KEY => [
                        'visibleFilters' => self::FILTERS,
                        'filterValues' => ['scope' => $scope, 'event' => '', 'employee' => '', 'company' => '', 'period' => ''],
                    ]]]),
                ]);
            }
        }
    }

    public function safeDown()
    {
        $this->delete('{{%backend_showing}}', ['key' => self::KEY, 'cms_user_id' => null, 'name' => ['Ждут моего решения', 'Все']]);
    }
}
