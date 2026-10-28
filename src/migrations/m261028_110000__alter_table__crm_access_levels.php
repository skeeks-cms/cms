<?php

use yii\db\Migration;

/**
 * П2а: the level of an employee's link to a company or a client — "deal"
 * (own deals), "edit" (own deals and the company data), "full". Existing links
 * are full; requests keep the level. The main responsible and the former
 * "sales" → "edit": m261028_140000.
 */
class m261028_110000__alter_table__crm_access_levels extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_company2manager}}', 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        $this->addColumn('{{%cms_user2manager}}', 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        $this->addColumn('{{%cms_crm_access_request}}', 'level', $this->string(16)->null()->comment('Уровень: deal, edit, full'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_crm_access_request}}', 'level');
        $this->dropColumn('{{%cms_user2manager}}', 'access_level');
        $this->dropColumn('{{%cms_company2manager}}', 'access_level');
    }
}
