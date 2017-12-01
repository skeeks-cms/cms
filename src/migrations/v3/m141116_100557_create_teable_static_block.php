<?php
/**
 * m141116_100557_create_teable_static_block
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
 * Class m141109_100557_create_cms_static_block_table
 */
class m141116_100557_create_teable_static_block extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_static_block}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cms_static_block}}', [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'code'                  => Schema::TYPE_STRING . '(32) NOT NULL',
            'description'           => Schema::TYPE_TEXT . ' NULL',
            'value'                 => Schema::TYPE_TEXT . ' NULL',

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_static_block}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_static_block}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_static_block}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%cms_static_block}} ADD INDEX(updated_by);");

        $this->execute("ALTER TABLE {{%cms_static_block}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_static_block}} COMMENT = 'Статические блоки';");

        $this->addForeignKey(
            'cms_static_block_created_by', "{{%cms_static_block}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_static_block_updated_by', "{{%cms_static_block}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_static_block_updated_by", "{{%cms_static_block}}");
        $this->dropForeignKey("cms_static_block_created_by", "{{%cms_static_block}}");

        $this->dropTable('{{%cms_static_block}}');
    }
}
