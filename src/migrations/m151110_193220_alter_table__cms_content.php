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

class m151110_193220_alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_content}}', 'meta_title_template', $this->string(500));
        $this->addColumn('{{%cms_content}}', 'meta_description_template', $this->text());
        $this->addColumn('{{%cms_content}}', 'meta_keywords_template', $this->text());
    }

    public function safeDown()
    {
        echo "m151110_193220_alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}