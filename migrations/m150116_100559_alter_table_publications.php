<?php
/**
 * m150116_100559_alter_table_publications
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.01.2015
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

class m150116_100559_alter_table_publications extends Migration
{
    public function up()
    {
        $this->addColumn('{{%cms_publication}}', 'tree_id', Schema::TYPE_INTEGER . ' NULL');
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(tree_id);");

        $this->addForeignKey(
            'cms_publication_tree_id_cms_tree', "{{%cms_publication}}",
            'tree_id', '{{%cms_tree}}', 'id', 'RESTRICT', 'RESTRICT'
        );

    }

    public function down()
    {
        $this->dropForeignKey("cms_publication_tree_id_cms_tree", "{{%cms_publication}}");
        $this->dropColumn('{{%cms_publication}}', 'tree_id');
    }
}
