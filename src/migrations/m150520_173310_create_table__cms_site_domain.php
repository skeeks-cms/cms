<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_173310_create_table__cms_site_domain extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_site_domain}}", true);
        if ($tableExist)
        {
            return true;
        }


        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_site_domain}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'site_code'             => "CHAR(5) NOT NULL",
            'domain'                => Schema::TYPE_STRING . '(255) NOT NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_site_domain}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_site_domain}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_site_domain}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_site_domain}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_site_domain}} ADD UNIQUE(domain, site_code);");

        $this->execute("ALTER TABLE {{%cms_site_domain}} COMMENT = 'Доменные имена сайтов';");

        $this->addForeignKey(
            'cms_site_domain_created_by', "{{%cms_site_domain}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_site_domain_updated_by', "{{%cms_site_domain}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );


        $this->addForeignKey(
            'cms_site_domain_site_code', "{{%cms_site_domain}}",
            'site_code', '{{%cms_site}}', 'code', 'CASCADE', 'CASCADE'
        );
    }

    public function down()
    {
        echo "m150520_173310_create_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}
