<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * П2а: the level of an employee's link to a company or a client — "deal"
 * (own deals), "edit" (own deals and the company data), "full". Existing links
 * are full; requests keep the level. The main responsible and the former
 * "sales" → "edit": m261028_140000.
 */
class m261028_110000__alter_table__crm_access_levels extends Migration
{
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        $this->addColumnIfMissing('{{%cms_company2manager}}', 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        $this->addColumnIfMissing('{{%cms_user2manager}}', 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        $this->addColumnIfMissing('{{%cms_crm_access_request}}', 'level', $this->string(16)->null()->comment('Уровень: deal, edit, full'));
    }

    public function safeDown()
    {
        $this->refuseRollbackWithData([
            'уровни доступа к компаниям ниже полного' => $this->hasColumn('{{%cms_company2manager}}', 'access_level')
                ? (new \yii\db\Query())->from('{{%cms_company2manager}}')->andWhere(['<>', 'access_level', 'full']) : null,
            'уровни доступа к клиентам ниже полного' => $this->hasColumn('{{%cms_user2manager}}', 'access_level')
                ? (new \yii\db\Query())->from('{{%cms_user2manager}}')->andWhere(['<>', 'access_level', 'full']) : null,
            'запросы с уровнем' => $this->hasColumn('{{%cms_crm_access_request}}', 'level')
                ? (new \yii\db\Query())->from('{{%cms_crm_access_request}}')->andWhere(['not', ['level' => null]]) : null,
        ]);
        $this->dropColumnIfExists('{{%cms_crm_access_request}}', 'level');
        $this->dropColumnIfExists('{{%cms_user2manager}}', 'access_level');
        $this->dropColumnIfExists('{{%cms_company2manager}}', 'access_level');
    }
}
