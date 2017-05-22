<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170515_023840__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        $this->dropColumn("{{%cms_content_property}}", "multiple_cnt");
        $this->dropColumn("{{%cms_content_property}}", "with_description");
        $this->dropColumn("{{%cms_content_property}}", "searchable");
        $this->dropColumn("{{%cms_content_property}}", "filtrable");
        $this->dropColumn("{{%cms_content_property}}", "version");
        $this->dropColumn("{{%cms_content_property}}", "smart_filtrable");
    }

    public function safeDown()
    {
        echo "m170512_023840__alter_table__cms_content_element_property cannot be reverted.\n";
        return false;
    }
}