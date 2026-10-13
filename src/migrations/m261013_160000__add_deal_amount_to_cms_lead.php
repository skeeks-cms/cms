<?php

use yii\db\Migration;

class m261013_160000__add_deal_amount_to_cms_lead extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%cms_lead}}',
            'deal_amount',
            $this->decimal(18, 2)->null()->after('result_comment')->comment('Сумма сделки по успешному лиду, разовая')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_lead}}', 'deal_amount');
    }
}
