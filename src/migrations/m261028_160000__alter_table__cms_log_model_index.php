<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * П2а+: the card feed of a company or a client also reads the records of its
 * deals (CmsLogQuery::clientDealsCondition()) — model_code and model_id
 * together. A company with thousands of calls stays fast.
 */
class m261028_160000__alter_table__cms_log_model_index extends Migration
{
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        $this->createIndexIfMissing('cms_log__model_code_id', '{{%cms_log}}', ['model_code', 'model_id']);
    }

    public function safeDown()
    {
        $this->dropIndexIfExists('cms_log__model_code_id', '{{%cms_log}}');
    }
}
