<?php
/**
 * LoginForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\forms;

use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Login form
 */
class PasswordChangeFormV2 extends Model
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['password'], 'required'],
            [['password'], function ($attribute) {

                $password = $this->{$attribute};
                $password = trim($password);
                
                $number = preg_match('@[0-9]@', $password);
                $uppercase = preg_match('@[A-Z]@', $password);
                $lowercase = preg_match('@[a-z]@', $password);
                $specialChars = preg_match('@[^\w]@', $password);

                $passLength = StringHelper::strlen($password);
                if ($passLength < \Yii::$app->cms->pass_required_length) {
                    $this->addError($attribute, "Пароль слишком короткий! Необходимо минимум " . \Yii::$app->cms->pass_required_length . " символов.");
                    return false;
                }
                if (!$number && \Yii::$app->cms->pass_required_need_number) {
                    $this->addError($attribute, "Пароль должен содержать как минимум одну цифру");
                    return false;
                }
                if (!$uppercase && \Yii::$app->cms->pass_required_need_uppercase) {
                    $this->addError($attribute, "Пароль должен хоть одну заглавную английскую букву");
                    return false;
                }
                if (!$lowercase && \Yii::$app->cms->pass_required_need_lowercase) {
                    $this->addError($attribute, "Пароль должен хоть одну строчную английскую букву");
                    return false;
                }
                if (!$specialChars && \Yii::$app->cms->pass_required_need_specialChars ) {
                    $this->addError($attribute, "Пароль должен содержать хоть один специальный символ");
                    return false;
                }
            }],
        ];
    }

    public function attributeLabels()
    {
        return [
            'password' => 'Пароль'
        ];
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function changePassword()
    {
        if ($this->validate()) {
            $this->user->setPassword($this->password);
            return $this->user->save(false);
        } else {
            return false;
        }
    }


}
