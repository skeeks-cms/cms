<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220811_100601__alter_table__cms_tree_type extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_tree_type";

        $this->addColumn($tableName, "meta_title_template", $this->string(500)->comment("Шаблон meta title"));
        $this->addColumn($tableName, "meta_description_template", $this->text()->comment("Шаблон meta description"));
        $this->addColumn($tableName, "meta_keywords_template", $this->text()->comment("Шаблон meta keywords"));
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}