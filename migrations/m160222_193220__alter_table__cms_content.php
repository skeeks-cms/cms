<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.02.2016
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m160222_193220__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_content}}', 'parent_content_id', $this->integer());
        $this->createIndex('parent_content_id', '{{%cms_content}}', 'parent_content_id');
        $this->addForeignKey(
            'cms_content__cms_content', "{{%cms_content}}",
            'parent_content_id', '{{%cms_content}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addColumn('{{%cms_content_element}}', 'parent_content_element_id', $this->integer());
        $this->createIndex('parent_content_element_id', '{{%cms_content_element}}', 'parent_content_element_id');
        $this->addForeignKey(
            'cms_content_element__cms_content_element', "{{%cms_content_element}}",
            'parent_content_element_id', '{{%cms_content_element}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_content_element__cms_content_element", "{{%cms_content_element}}");
        $this->dropForeignKey("cms_content__cms_content", "{{%cms_content}}");

        $this->dropIndex('parent_content_id', '{{%cms_content}}');
        $this->dropIndex('parent_content_element_id', '{{%cms_content_element}}');

        $this->dropColumn('{{%cms_content}}', 'parent_content_id');
        $this->dropColumn('{{%cms_content_element}}', 'parent_content_element_id');
    }
}