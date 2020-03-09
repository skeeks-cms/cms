<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200307_181000__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_content_property}}", "cms_measure_code", $this->string(20));

        $this->addForeignKey(
            "cms_content_property__measure_code", "{{%cms_content_property}}",
            'cms_measure_code', '{{%cms_measure}}', 'code', 'RESTRICT', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200129_095515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}