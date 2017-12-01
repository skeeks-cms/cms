<?php
/**
 * m141109_100557_create_cms_infoblock_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m141109_100557_create_cms_infoblock_table
 */
class m141109_100557_create_cms_infoblock_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_infoblock}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cms_infoblock}}', [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING . '(255) NOT NULL',
            'code'                  => Schema::TYPE_STRING . '(32) NULL',
            'description'           => Schema::TYPE_TEXT . ' NULL',

            'widget'                => Schema::TYPE_STRING . '(255) NULL', //widget class
            'config'                => Schema::TYPE_TEXT . ' NULL', //widget config in serialize
            'rules'                 => Schema::TYPE_TEXT . ' NULL', //правила показа
            'template'              => Schema::TYPE_STRING . '(255) NULL', //шаблон виджета
            'priority'              => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0', //шаблон виджета

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10',

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(updated_by);");

        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(widget);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD INDEX(template);");
        $this->execute("ALTER TABLE {{%cms_infoblock}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_infoblock}} COMMENT = 'Инфоблоки';");

        $this->addForeignKey(
            'cms_infoblock_created_by', "{{%cms_infoblock}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_infoblock_updated_by', "{{%cms_infoblock}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_infoblock_updated_by", "{{%cms_infoblock}}");
        $this->dropForeignKey("cms_infoblock_created_by", "{{%cms_infoblock}}");

        $this->dropTable('{{%cms_infoblock}}');
    }
}
