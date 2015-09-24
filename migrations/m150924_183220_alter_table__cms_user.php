<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m150924_183220_alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getSchema()->getTableSchema('cms_user', true);
        foreach ($schema->foreignKeys as $fkData)
        {
            if ($fkData[0] == 'cms_user_phone')
            {
                $this->dropForeignKey('cms_user_tree_cms_user_phone', 'cms_user');
            }

            if ($fkData[0] == 'cms_user_email')
            {
                $this->dropForeignKey('cms_user_tree_cms_user_email', 'cms_user');
            }


        }

        $this->dropColumn('cms_user', 'email');
        $this->dropColumn('cms_user', 'phone');
    }

    public function safeDown()
    {
        echo "m150924_183220_alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}