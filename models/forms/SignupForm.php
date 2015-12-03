<?php
/**
 * SignupForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\forms;

use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\User;
use yii\base\Model;
use Yii;

/**
 * Class SignupForm
 * @package skeeks\cms\models\forms
 */
class SignupForm extends Model
{
    const SCENARION_FULLINFO    = 'fullInfo';
    const SCENARION_ONLYEMAIL   = 'onlyEmail';

    public $username;
    public $email;
    public $password;

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'username'      => \Yii::t('app','Login'),
            'email'         => \Yii::t('app','Email'),
            'password'      => \Yii::t('app','Password'),
        ];
    }

    public function scenarios()
    {
        $scenarions = parent::scenarios();

        $scenarions[self::SCENARION_FULLINFO] = [
            'username', 'email', 'password'
        ];

        $scenarions[self::SCENARION_ONLYEMAIL] = [
            'email'
        ];

        return $scenarions;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => \Yii::$app->cms->getUserClassName(), 'message' => \Yii::t('app','This login is already in use by another user.')],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            //['email', 'unique', 'targetClass' => \Yii::$app->cms->getUserClassName(), 'message' => 'Этот Email уже занят другим пользователем.'],

            [['email'], 'unique', 'targetClass' => CmsUserEmail::className(), 'targetAttribute' => 'value'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate())
        {
            /**
             * @var User $user
             */
            $userClassName          = \Yii::$app->cms->getUserClassName();
            $user                   = new $userClassName();

            if ($this->scenario == self::SCENARION_FULLINFO)
            {
                $user->username         = $this->username;
                $user->email            = $this->email;
                $user->setPassword($this->password);
                $user->generateAuthKey();
                $user->save();

                return $user;

            } else if ($this->scenario == self::SCENARION_ONLYEMAIL)
            {

                $password               = \Yii::$app->security->generateRandomString(6);

                $user->generateUsername();
                $user->setPassword($password);
                $user->email            = $this->email;
                $user->generateAuthKey();

                $user->save();

                if ($user)
                {
                    \Yii::$app->mailer->compose('registerByEmail', [
                        'user'      => $user,
                        'password'  => $password
                    ])
                        ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName . ''])
                        ->setTo($user->email)
                        ->setSubject(\Yii::t('app','Sign up at site') . \Yii::$app->cms->appName)
                        ->send();
                }

                return $user;
            }

        }


        return null;
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return boolean whether the email was send
     */
    public function sendEmail()
    {
        /* @var $user User */

        if ($user = User::findByEmail($this->email))
        {
            if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
                $user->generatePasswordResetToken();
            }

            if ($user->save()) {
                return \Yii::$app->mailer->compose('passwordResetToken', ['user' => $user])
                    ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName . ' robot'])
                    ->setTo($this->email)
                    ->setSubject(\Yii::t('app','Password reset for ') . \Yii::$app->cms->appName)
                    ->send();
            }
        }

        return false;
    }
}
