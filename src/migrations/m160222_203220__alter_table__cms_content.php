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

class m160222_203220__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_content}}', 'visible', $this->string(1)->notNull()->defaultValue("Y"));
        $this->createIndex('visible', '{{%cms_content}}', 'visible');

        $this->addColumn('{{%cms_content}}', 'parent_content_on_delete', $this->string(10)->notNull()->defaultValue("CASCADE"));
        $this->createIndex('parent_content_on_delete', '{{%cms_content}}', 'parent_content_on_delete');

        $this->addColumn('{{%cms_content}}', 'parent_content_is_required', $this->string(1)->notNull()->defaultValue("Y"));
        $this->createIndex('parent_content_is_required', '{{%cms_content}}', 'parent_content_is_required');

    }

    public function safeDown()
    {
        $this->dropIndex('visible', '{{%cms_content}}');
        $this->dropColumn('{{%cms_content}}', 'visible');

        $this->dropIndex('parent_content_on_delete', '{{%cms_content}}');
        $this->dropColumn('{{%cms_content}}', 'parent_content_on_delete');

        $this->dropIndex('parent_content_is_required', '{{%cms_content}}');
        $this->dropColumn('{{%cms_content}}', 'parent_content_is_required');
    }
}