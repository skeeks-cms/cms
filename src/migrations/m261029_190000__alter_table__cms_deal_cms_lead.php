<?php

use yii\db\Migration;

/**
 * A service (CmsDeal) is tied to a deal (CmsLead) — «Полноценная работа в
 * рамках своих сделок» (owner's decision 29.10.2026): the levels «Свои сделки»
 * and "edit" see the services of their deals (CrmAccess::ownLeadCondition()),
 * the deal card shows them. A repeated run changes nothing.
 */
class m261029_190000__alter_table__cms_deal_cms_lead extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%cms_deal}}', true);
        if ($schema && $schema->getColumn('cms_lead_id') === null) {
            $this->addColumn('{{%cms_deal}}', 'cms_lead_id', $this->integer()->null()->comment('Сделка'));
        }
        if (!$this->hasIndex('cms_deal__cms_lead_id')) {
            $this->createIndex('cms_deal__cms_lead_id', '{{%cms_deal}}', 'cms_lead_id');
        }
        if (!$this->hasForeignKey('cms_deal__cms_lead_id')) {
            $this->addForeignKey('cms_deal__cms_lead_id', '{{%cms_deal}}', 'cms_lead_id', '{{%cms_lead}}', 'id', 'SET NULL', 'SET NULL');
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%cms_deal}}', true);
        if (!$schema || $schema->getColumn('cms_lead_id') === null) {
            return;
        }
        // MySQL does not roll DDL back: refuse before the change rather than lose the links.
        $used = (int)(new \yii\db\Query())->from('{{%cms_deal}}')->andWhere(['not', ['cms_lead_id' => null]])->count('*', $this->db);
        if ($used) {
            throw new \yii\base\Exception('Откат невозможен: '.$used.' услуг привязаны к сделкам, привязка пропала бы. Схема не изменена.');
        }
        if ($this->hasForeignKey('cms_deal__cms_lead_id')) {
            $this->dropForeignKey('cms_deal__cms_lead_id', '{{%cms_deal}}');
        }
        if ($this->hasIndex('cms_deal__cms_lead_id')) {
            $this->dropIndex('cms_deal__cms_lead_id', '{{%cms_deal}}');
        }
        $this->dropColumn('{{%cms_deal}}', 'cms_lead_id');
    }

    private function hasIndex(string $name): bool
    {
        return (bool)$this->db->createCommand('SHOW INDEX FROM {{%cms_deal}} WHERE Key_name = :name', [':name' => $name])->queryOne();
    }

    private function hasForeignKey(string $name): bool
    {
        return isset($this->db->getTableSchema('{{%cms_deal}}', true)->foreignKeys[$name]);
    }
}
