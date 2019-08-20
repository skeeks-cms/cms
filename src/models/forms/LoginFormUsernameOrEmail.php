<?php
/**
 * Форма позволяющая авторизовываться использую логин или email
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.02.2015
 * @since 1.0.0
 */

namespace skeeks\cms\models\forms;

use skeeks\cms\models\User;
use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginFormUsernameOrEmail extends Model
{
    public $identifier;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['identifier', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],

            ['identifier', 'validateEmailIsApproved'],
        ];
    }



    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'identifier' => \Yii::t('skeeks/cms', 'Username or Email'),
            'password' => \Yii::t('skeeks/cms', 'Password'),
            'rememberMe' => \Yii::t('skeeks/cms', 'Remember me'),
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateEmailIsApproved($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (\Yii::$app->cms->auth_only_email_is_approved && !$user->email_is_approved) {
                $this->addError($attribute, \Yii::t('skeeks/cms', 'Вам необходимо подтвердить ваш email. Для этого перейдите по ссылке из письма.'));
            }
        }
    }
    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, \Yii::t('skeeks/cms', 'Incorrect username or password.'));
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        } else {
            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsernameOrEmail($this->identifier);
        }

        return $this->_user;
    }
}
