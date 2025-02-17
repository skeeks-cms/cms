<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m240607_182718_alter_table__cms_content_element extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        /*$tableName = 'cms_content_element';*/

        $this->execute("ALTER TABLE `cms_content_element` CHANGE `description_short` `description_short` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;");
        $this->execute("ALTER TABLE `cms_content_element` CHANGE `description_full` `description_full` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;");
    }

    public function safeDown()
    {
        /*$this->dropTable("{{%cms_content_element}}");*/
    }
}
