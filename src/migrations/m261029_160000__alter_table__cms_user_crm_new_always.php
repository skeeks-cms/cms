<?php

use yii\db\Migration;

/**
 * The setting of an employee «Новые компании и клиенты, которые я создаю, —
 * „Всегда по согласованию“» (owner's decision 29.10.2026): a company or a
 * person created on his behalf (the forms, the deal form, an import, REST and
 * MCP) gets the protection `always` at once (crm\CrmProtection::onCreated()).
 */
class m261029_160000__alter_table__cms_user_crm_new_always extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_user}}', 'crm_new_always', $this->tinyInteger(1)->notNull()->defaultValue(0)
            ->comment('Новые клиенты сотрудника — всегда по согласованию'));
    }

    public function safeDown()
    {
        // MySQL does not roll DDL back: refuse before the change rather than lose the settings of employees.
        $used = (int)(new \yii\db\Query())->from('{{%cms_user}}')->andWhere(['crm_new_always' => 1])->count('*', $this->db);
        if ($used) {
            throw new \yii\base\Exception('Откат невозможен: у '.$used.' сотрудников включено «Новые клиенты — всегда по согласованию», настройка пропала бы. Выключите её, если откат действительно нужен. Схема не изменена.');
        }
        $this->dropColumn('{{%cms_user}}', 'crm_new_always');
    }
}
