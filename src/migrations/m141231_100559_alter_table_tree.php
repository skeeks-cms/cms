<?php
/**
 * m141231_100559_alter_table_tree
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.12.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

class m141231_100559_alter_table_tree extends Migration
{
    public function up()
    {
        $this->addColumn('{{%cms_tree}}', 'redirect', Schema::TYPE_STRING . '(500) NULL');
        $this->addColumn('{{%cms_tree}}', 'tree_ids', Schema::TYPE_STRING . '(500) NULL');
        $this->addColumn('{{%cms_tree}}', 'tree_menu_ids', Schema::TYPE_STRING . '(500) NULL');


        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(redirect);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(tree_ids);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(tree_menu_ids);");

    }

    public function down()
    {
        $this->dropColumn('{{%cms_tree}}', 'tree_ids');
        $this->dropColumn('{{%cms_tree}}', 'tree_menu_ids');
        $this->dropColumn('{{%cms_tree}}', 'redirect');
    }
}
