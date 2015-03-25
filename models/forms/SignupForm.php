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

use yii\base\Model;
use Yii;

/**
 * Class SignupForm
 * @package skeeks\cms\models\forms
 */
class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'username'      => 'Логин',
            'email'         => 'Email',
            'password'      => 'Пароль',
        ];
    }

    public function scenarios()
    {
        $scenarions = parent::scenarios();

        $scenarions['fullInfo'] = [
            'username', 'email', 'password'
        ];

        $scenarions['onlyEmail'] = [
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
            ['username', 'unique', 'targetClass' => \Yii::$app->cms->getUserClassName(), 'message' => 'Этот логин уже занят другим пользователем.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'unique', 'targetClass' => \Yii::$app->cms->getUserClassName(), 'message' => 'Этот Email уже занят другим пользователем.'],

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
            $userClassName          = \Yii::$app->cms->getUserClassName();

            $user                   = new $userClassName();
            $user->username         = $this->username;
            $user->email            = $this->email;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->save();

            return $user;
        }

        return null;
    }
}
