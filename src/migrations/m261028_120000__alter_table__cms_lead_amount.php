<?php

use yii\db\Migration;

/**
 * П2а: «Сумма» of an open deal (the expected budget, as in amoCRM and
 * Bitrix24), set already in the deal form. deal_amount stays the agreed
 * amount of a successful deal; the success form proposes «Сумма» there.
 */
class m261028_120000__alter_table__cms_lead_amount extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_lead}}', 'amount', $this->decimal(18, 2)->null()->comment('Сумма (ожидаемая)'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_lead}}', 'amount');
    }
}
