<?php

use yii\db\Migration;

class m261015_020000__add_delivery_address_to_cms_contractor extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%cms_contractor}}',
            'delivery_address',
            $this->string(255)->null()->after('mailing_postcode')->comment('Адрес доставки: адрес грузополучателя в УПД, накладной и счёте-фактуре')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_contractor}}', 'delivery_address');
    }
}
