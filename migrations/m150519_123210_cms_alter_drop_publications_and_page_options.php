<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150519_123210_cms_alter_drop_publications_and_page_options extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `page_options`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `page_options`;");

        $tableExist = $this->db->getTableSchema("{{%cms_publication}}", true);
        if ($tableExist)
        {
            $this->execute("DROP TABLE {{%cms_publication}}");
        }
    }

    public function down()
    {
        echo "m150519_123210_cms_alter_drop_publications_and_page_options cannot be reverted.\n";
        return false;
    }
}
