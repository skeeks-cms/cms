<?php
/**
 * m150121_273205_alter_table__cms_user__add_emails
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

class m150121_273205_alter_table__cms_user__add_emails extends Migration
{
    public function up()
    {
        $this->execute("ALTER TABLE {{%cms_user}} CHANGE `email` `email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
        $this->execute("INSERT INTO `cms_user_email` (`id`, `user_id`, `value`, `approved`, `approved_key`, `created_at`, `updated_at`) VALUES (NULL, '1', 'admin@skeeks.com', NULL, NULL, NULL, NULL);");


        //Вставляем mails в базу
        /*$users = \skeeks\cms\models\User::find()->all();
        if ($users)
        {
            foreach ($users as $user)
            {
                if ($user->email)
                {
                    $userEmail = new \skeeks\cms\models\user\UserEmail([
                        'value' => $user->email,
                        'user_id' => $user->id
                    ]);

                    $userEmail->save(false);
                } else
                {
                    $user->email = null;
                    $user->save(false);
                }
            }
        }*/

        $this->addForeignKey(
            'cms_user_tree_cms_user_email', "{{%cms_user}}",
            'email', '{{%cms_user_email}}', 'value', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_user_tree_cms_user_phone', "{{%cms_user}}",
            'phone', '{{%cms_user_phone}}', 'value', 'RESTRICT', 'RESTRICT'
        );

    }

    public function down()
    {
        echo "m150121_273205_alter_table__cms_user__add_emails cannot be reverted.\n";
        return false;
    }
}
