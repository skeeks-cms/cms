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
        $tableExist = $this->db->getTableSchema("{{%user}}", true);
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

        $this->createTable('{{%user}}', [
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

            'image'                 => Schema::TYPE_TEXT. ' NULL', //главное изображение
            'image_cover'           => Schema::TYPE_TEXT. ' NULL', //обложка
            'images'                => Schema::TYPE_TEXT. ' NULL', //
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

        $this->execute("ALTER TABLE {{%user}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%user}} ADD UNIQUE(username);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(email);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(password_hash);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(password_reset_token);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(city);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(address);");

        $this->execute("ALTER TABLE {{%user}} ADD INDEX(count_comment);");

        $this->execute("ALTER TABLE {{%user}} ADD INDEX(count_vote);");
        $this->execute("ALTER TABLE {{%user}} ADD INDEX(result_vote);");

        $this->execute("ALTER TABLE {{%user}} ADD INDEX(count_subscribe);");

        $this->execute("ALTER TABLE {{%user}} ADD `gender` ENUM(\"men\",\"women\") NOT NULL DEFAULT 'men' ;");

        $this->execute("ALTER TABLE {{%user}} COMMENT = 'Пользователь';");

        $this->insert('{{%user}}', [
            "username"              => "semenov",
            "name"                  => "Семенов Александр",
            "city"                  => "Зеленоград",
            "address"               => "Зеленоград, ул. Каменка, 2004-25",
            "auth_key"              => "otv60YW-nV6-8GRI4La3vYNhu_-dmp_n",
            "password_hash"         => '$2y$13$uQTuHAVweWENBA08dpsb7Ov2p0sj4t1c9AV8S1OkXszwBQB/gBToS',
            "password_reset_token"  => 'wn49wJLj9OMVjgj8bBzBjND4nFixyJgt_1413297645',
            "email"                 => 'semenov@skeeks.com',
            "role"                  => 10,
            "status"                => 10,
        ]);
    }

    public function down()
    {
        $this->dropTable('{{%user}}');
    }
}
