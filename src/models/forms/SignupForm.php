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
use skeeks\cms\validators\PhoneValidator;
use yii\base\Model;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class SignupForm
 * @package skeeks\cms\models\forms
 */
class SignupForm extends Model
{
    const SCENARION_FULLINFO = 'fullInfo';
    const SCENARION_ONLYEMAIL = 'onlyEmail';
    const SCENARION_SHORTINFO = 'shortInfo';

    public $username;
    public $email;
    public $password;

    public $first_name;
    public $last_name;
    public $patronymic;
    public $phone;


    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'username' => \Yii::t('skeeks/cms', 'Login'),
            'email' => \Yii::t('skeeks/cms', 'Email'),
            'password' => \Yii::t('skeeks/cms', 'Password'),
            'first_name' => \Yii::t('skeeks/cms', 'First name'),
            'last_name' => \Yii::t('skeeks/cms', 'Last name'),
            'patronymic' => \Yii::t('skeeks/cms', 'Patronymic'),

            'email' => Yii::t('skeeks/cms', 'Email'),
            'phone' => Yii::t('skeeks/cms', 'Phone'),
        ];
    }

    public function scenarios()
    {
        $scenarions = parent::scenarios();

        $scenarions[self::SCENARION_FULLINFO] = [
            'username',
            'email',
            'password',

            'first_name',
            'last_name',
            'patronymic',
        ];

        $scenarions[self::SCENARION_SHORTINFO] = [
            'email',
            'password',
            'phone',
            'first_name',
            'last_name',
            'patronymic',
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
            [
                'username',
                'unique',
                'targetClass' => \Yii::$app->user->identityClass,
                'message' => \Yii::t('skeeks/cms', 'This login is already in use by another user.')
            ],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            [
                'email',
                'unique',
                'targetClass' => \Yii::$app->user->identityClass,
                'message' => \Yii::t('skeeks/cms', 'This Email is already in use by another user')
            ],

            //[['email'], 'unique', 'targetClass' => CmsUserEmail::className(), 'targetAttribute' => 'value'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6],

            [
                ['first_name', 'last_name', 'patronymic'],
                'string',
                'max' => 255
            ],

            [['phone'], 'string', 'max' => 64],
            [['phone'], PhoneValidator::class],
            [['phone'], 'unique', 'targetClass' => \Yii::$app->user->identityClass],
            [['phone'], 'default', 'value' => null],
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate()) {
            /**
             * @var User $user
             */
            $userClassName = \Yii::$app->user->identityClass;
            $user = new $userClassName();

            if ($this->scenario == self::SCENARION_FULLINFO) {
                $user->username = $this->username;
                $user->email = $this->email;
                $user->last_name = $this->last_name;
                $user->first_name = $this->first_name;
                $user->patronymic = $this->patronymic;
                $user->phone = $this->phone;
                $user->setPassword($this->password);
                $user->generateAuthKey();
                $user->save();

                return $user;

            } elseif ($this->scenario == self::SCENARION_SHORTINFO) {
                $user->generateUsername();
                $user->email = $this->email;
                $user->last_name = $this->last_name;
                $user->first_name = $this->first_name;
                $user->patronymic = $this->patronymic;
                $user->phone = $this->phone;
                $user->setPassword($this->password);
                $user->generateAuthKey();
                $user->save();

                return $user;

            } else {
                if ($this->scenario == self::SCENARION_ONLYEMAIL) {

                    $password = \Yii::$app->security->generateRandomString(6);

                    $user->generateUsername();
                    $user->setPassword($password);
                    $user->email = $this->email;
                    $user->generateAuthKey();

                    if ($user->save()) {
                        \Yii::$app->mailer->view->theme->pathMap = ArrayHelper::merge(\Yii::$app->mailer->view->theme->pathMap,
                            [
                                '@app/mail' =>
                                    [
                                        '@skeeks/cms/mail-templates'
                                    ]
                            ]);

                        \Yii::$app->mailer->compose('@app/mail/register-by-email', [
                            'user' => $user,
                            'password' => $password
                        ])
                            ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName . ''])
                            ->setTo($user->email)
                            ->setSubject(\Yii::t('skeeks/cms', 'Sign up at site') . \Yii::$app->cms->appName)
                            ->send();

                        return $user;
                    } else {
                        \Yii::error("User rgister by email error: {$user->username} " . Json::encode($user->getFirstErrors()),
                            'RegisterError');
                        return null;
                    }
                }
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

        if ($user = User::findByEmail($this->email)) {
            if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
                $user->generatePasswordResetToken();
            }

            if ($user->save()) {

                \Yii::$app->mailer->view->theme->pathMap = ArrayHelper::merge(\Yii::$app->mailer->view->theme->pathMap,
                    [
                        '@app/mail' =>
                            [
                                '@skeeks/cms/mail'
                            ]
                    ]);

                return \Yii::$app->mailer->compose('@app/mail/password-reset-token', ['user' => $user])
                    ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName . ' robot'])
                    ->setTo($this->email)
                    ->setSubject(\Yii::t('skeeks/cms', 'Password reset for ') . \Yii::$app->cms->appName)
                    ->send();
            }
        }

        return false;
    }
}
