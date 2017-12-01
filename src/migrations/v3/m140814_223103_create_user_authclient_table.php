<?php
/**
 * Создание таблицы где будут храниться профили авторизации через соц сети.
 * И их связи с юзерами
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.10.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m140814_223103_create_user_authclient_table
 */
class m140814_223103_create_user_authclient_table extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_user_authclient}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_user_authclient}}", [
            'id'                    => Schema::TYPE_PK,
            'user_id'               => Schema::TYPE_INTEGER . ' NOT NULL',
            'provider'              => Schema::TYPE_STRING. '(50)',
            'provider_identifier'   => Schema::TYPE_STRING. '(100)',
            'provider_data'         => Schema::TYPE_TEXT,
            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',
        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_user_authclient}} ADD INDEX(user_id);");
        $this->execute("ALTER TABLE {{%cms_user_authclient}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_user_authclient}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_user_authclient}} ADD INDEX(provider);");
        $this->execute("ALTER TABLE {{%cms_user_authclient}} ADD INDEX(provider_identifier);");

        $this->addForeignKey(
            'fk_user_id', "{{%cms_user_authclient}}",
            'user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function down()
    {
        $this->dropForeignKey("fk_user_id", "{{%cms_user_authclient}}");
        $this->dropTable("{{%cms_user_authclient}}");
    }
}
