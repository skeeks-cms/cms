<?php
/**
 * m141231_100557_create_teable_cms_tree_menu
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.12.2014
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m141231_100557_create_teable_cms_tree_menu
 */
class m141231_100557_create_teable_cms_tree_menu extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_tree_menu}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cms_tree_menu}}', [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING . '(255) NOT NULL',
            'description'           => Schema::TYPE_TEXT . ' NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_tree_menu}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_tree_menu}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_tree_menu}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%cms_tree_menu}} ADD INDEX(updated_by);");

        $this->execute("ALTER TABLE {{%cms_tree_menu}} ADD UNIQUE(name);");

        $this->execute("ALTER TABLE {{%cms_tree_menu}} COMMENT = 'Позиции меню';");

        $this->addForeignKey(
            'cms_tree_menu_created_by', "{{%cms_tree_menu}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_tree_menu_updated_by', "{{%cms_tree_menu}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

    }

    public function down()
    {
        $this->dropForeignKey("cms_tree_menu_created_by", "{{%cms_tree_menu}}");
        $this->dropForeignKey("cms_tree_menu_updated_by", "{{%cms_tree_menu}}");

        $this->dropTable('{{%cms_tree_menu}}');
    }
}
