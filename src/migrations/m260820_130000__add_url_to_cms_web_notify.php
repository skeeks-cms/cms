<?php

use yii\db\Migration;

class m260820_130000__add_url_to_cms_web_notify extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%cms_web_notify}}',
            'url',
            $this->string(1000)->null()->after('comment')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_web_notify}}', 'url');
    }
}
