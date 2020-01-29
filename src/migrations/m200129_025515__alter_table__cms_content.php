<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200129_025515__alter_table__cms_content extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_content}}", "element_notice", $this->text());
        $this->addColumn("{{%cms_content}}", "list_notice", $this->text());
    }

    public function safeDown()
    {
        echo "m200129_025515__alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}