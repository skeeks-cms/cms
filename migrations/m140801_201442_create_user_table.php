<?php
/**
 * m140801_201442_create_user_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m140801_201442_create_user_table
 */
class m140801_201442_create_user_table extends Migration
{
    public function up()
    {
        $authManager = $this->getAuthManager();

        $tableExist = $this->db->getTableSchema("{{%cms_user}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cms_user}}', [
            'id'                    => Schema::TYPE_PK,
            'username'              => Schema::TYPE_STRING . ' NOT NULL',
            'auth_key'              => Schema::TYPE_STRING . '(32) NOT NULL',
            'password_hash'         => Schema::TYPE_STRING . ' NOT NULL',
            'password_reset_token'  => Schema::TYPE_STRING,
            'email'                 => Schema::TYPE_STRING . ' NOT NULL',
            'role'                  => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10',

            'status'                => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 10',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING . '(255)',
            'city'                  => Schema::TYPE_STRING . '(255)',
            'address'               => Schema::TYPE_STRING . '(255)',
            'info'                  => Schema::TYPE_TEXT,

            'files'                 => Schema::TYPE_TEXT. ' NULL', //

            'count_subscribe'       => Schema::TYPE_INTEGER . ' NULL',
            'users_subscribers'     => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые подписались (их id через запятую)

            'count_comment'         => Schema::TYPE_INTEGER . ' NULL', //Количество комментариев привязанных к пользователю.

            'status_of_life'        => Schema::TYPE_STRING . '(255)', //Коротки текстовый статус на странице


            'count_vote'            => Schema::TYPE_INTEGER . ' NULL', //Количество голосов
            'result_vote'           => Schema::TYPE_INTEGER . ' NULL', //Результат голосования
            'users_votes_up'        => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали +
            'users_votes_down'      => Schema::TYPE_TEXT. ' NULL',   //Пользователи которые проголосовали -
        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD UNIQUE(username);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(email);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(password_hash);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(password_reset_token);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(city);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(address);");

        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(count_comment);");

        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(count_subscribe);");

        $this->execute("ALTER TABLE {{%cms_user}} ADD `gender` ENUM(\"men\",\"women\") NOT NULL DEFAULT 'men' ;");

        $this->execute("ALTER TABLE {{%cms_user}} COMMENT = 'Пользователь';");



        $this->addForeignKey(
            'auth_assignment_user_id', $authManager->assignmentTable,
            'user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    /**
     * @throws yii\base\InvalidConfigException
     * @return \yii\rbac\DbManager
     */
    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof \yii\rbac\DbManager) {
            throw new \yii\base\InvalidConfigException('You should configure "authManager" component to use database before executing this migration.');
        }
        return $authManager;
    }

    public function down()
    {
        $authManager = $this->getAuthManager();

        $this->dropForeignKey("auth_assignment_user_id", $authManager->assignmentTable);
        $this->dropTable('{{%cms_user}}');
    }
}
