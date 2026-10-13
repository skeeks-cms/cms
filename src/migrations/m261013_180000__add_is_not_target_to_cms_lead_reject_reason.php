<?php

use yii\db\Migration;

class m261013_180000__add_is_not_target_to_cms_lead_reject_reason extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%cms_lead_reject_reason}}',
            'is_not_target',
            $this->boolean()->notNull()->defaultValue(0)->after('is_no_answer')->comment('Нецелевая заявка: не учитывается в конверсии')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_lead_reject_reason}}', 'is_not_target');
    }
}
