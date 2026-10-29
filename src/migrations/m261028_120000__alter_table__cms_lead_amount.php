<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * П2а: «Сумма» of an open deal (the expected budget, as in amoCRM and
 * Bitrix24), set already in the deal form. deal_amount stays the agreed
 * amount of a successful deal; the success form proposes «Сумма» there.
 *
 * safeDown refuses while a deal has «Сумма» (release audit N4, 29.10.2026):
 * dropping the column would erase it.
 */
class m261028_120000__alter_table__cms_lead_amount extends Migration
{
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        $this->addColumnIfMissing('{{%cms_lead}}', 'amount', $this->decimal(18, 2)->null()->comment('Сумма (ожидаемая)'));
    }

    public function safeDown()
    {
        $this->refuseRollbackWithData([
            'сделки с заполненной «Суммой»' => $this->hasColumn('{{%cms_lead}}', 'amount')
                ? (new \yii\db\Query())->from('{{%cms_lead}}')->andWhere(['not', ['amount' => null]]) : null,
        ]);
        $this->dropColumnIfExists('{{%cms_lead}}', 'amount');
    }
}
