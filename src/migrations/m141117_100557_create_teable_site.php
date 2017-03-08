<?php
/**
 * m141117_100557_create_teable_site
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.11.2014
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m141109_100557_create_cms_site_table
 */
class m141117_100557_create_teable_site extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_site}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cms_site}}', [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'host_name'             => Schema::TYPE_STRING . '(255) NULL',
            'name'                  => Schema::TYPE_STRING . '(255) NULL',
            'description'           => Schema::TYPE_TEXT . ' NULL',
            'params'                => Schema::TYPE_TEXT . ' NULL',

            'cms_tree_id'           => Schema::TYPE_INTEGER . ' NULL',
        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(updated_by);");

        $this->execute("ALTER TABLE {{%cms_site}} ADD UNIQUE(host_name);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD UNIQUE(cms_tree_id);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(name);");

        $this->execute("ALTER TABLE {{%cms_site}} COMMENT = 'Заргеистрированные сайты';");

        $this->addForeignKey(
            'cms_site_created_by', "{{%cms_site}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_site_updated_by', "{{%cms_site}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );


        $this->addForeignKey(
            'cms_site_cms_tree_id', "{{%cms_site}}",
            'cms_tree_id', '{{%cms_tree}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_site_cms_tree_id", "{{%cms_site}}");
        $this->dropForeignKey("cms_site_updated_by", "{{%cms_site}}");
        $this->dropForeignKey("cms_site_created_by", "{{%cms_site}}");

        $this->dropTable('{{%cms_site}}');
    }
}
