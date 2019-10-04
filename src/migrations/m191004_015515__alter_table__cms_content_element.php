<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m191004_015515__alter_table__cms_content_element extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_content_element}}", "seo_h1", $this->string(255)->notNull());
        $this->createIndex("seo_h1", "{{%cms_content_element}}", "seo_h1");
    }

    public function safeDown()
    {
        echo "m191004_015515__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}