<?php

use yii\db\Migration;

class m261013_100000__add_no_answer_count_to_cms_lead extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'cms_lead',
            'no_answer_count',
            $this->integer()->unsigned()->notNull()->defaultValue(0)->comment('Неудачных попыток дозвониться')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('cms_lead', 'no_answer_count');
    }
}
