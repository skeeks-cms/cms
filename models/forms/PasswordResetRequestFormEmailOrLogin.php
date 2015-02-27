<?php
/**
 * PasswordResetRequestFormEmailOrLogin
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2015
 */
namespace skeeks\cms\models\forms;
use yii\base\Model;
use yii\web\User;

/**
 * Class PasswordResetRequestFormEmailOrLogin
 * @package skeeks\cms\models\forms
 */
class PasswordResetRequestFormEmailOrLogin extends Model
{
    public $identifier;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $identityClassName = \Yii::$app->user->identityClass;
        return [
            ['identifier', 'filter', 'filter' => 'trim'],
            ['identifier', 'required'],
            ['identifier', 'validateEdentifier'],
            /*['email', 'exist',
                'targetClass' => $identityClassName,
                'filter' => ['status' => $identityClassName::STATUS_ACTIVE],
                'message' => 'There is no user with such email.'
            ],*/
        ];
    }

    public function validateEdentifier($attr)
    {
        $identityClassName = \Yii::$app->user->identityClass;
        $user = $identityClassName::findByUsernameOrEmail($this->identifier);

        if (!$user)
        {
            $this->addError($attr, 'Пользователь не найден');
        }
    }
    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return boolean whether the email was send
     */
    public function sendEmail()
    {
        /* @var $user User */
        $identityClassName = \Yii::$app->user->identityClass;

        $user = $identityClassName::findByUsernameOrEmail($this->identifier);
        //$user = $identityClassName::

        if ($user) {
            if (!$identityClassName::isPasswordResetTokenValid($user->password_reset_token)) {
                $user->generatePasswordResetToken();
            }

            if ($user->save()) {

                \Yii::$app->mailer->setViewPath(\Yii::$app->cms->moduleCms()->basePath . '/mail');

                return \Yii::$app->mailer->compose('passwordResetToken', ['user' => $user])
                    ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name])
                    ->setTo($user->email)
                    ->setSubject('Сброс пароля для ' . \Yii::$app->name)
                    ->send();
            }
        }

        return false;
    }
}
