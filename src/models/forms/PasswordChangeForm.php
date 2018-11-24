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

use skeeks\cms\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Login form
 */
class PasswordChangeForm extends Model
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $new_password;

    /**
     * @var
     */
    public $new_password_confirm;

    const SCENARION_NOT_REQUIRED = 'notRequired';

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARION_NOT_REQUIRED => $scenarios[self::SCENARIO_DEFAULT],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // password is validated by validatePassword()
            /*[['new_password_confirm', 'new_password'], 'required'/*, 'when' => function(self $model)
            {
                return $model->scenario != self::SCENARION_NOT_REQUIRED;
            }],*/
            /*],*/
            [['new_password_confirm', 'new_password'], 'string', 'min' => 6],
            [['new_password_confirm'], 'validateNewPassword'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'new_password' => \Yii::t('skeeks/cms', 'New password'),
            'new_password_confirm' => \Yii::t('skeeks/cms', 'New Password Confirm'),
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateNewPassword($attribute, $params)
    {
        if ($this->new_password_confirm != $this->new_password) {
            $this->addError($attribute, \Yii::t('skeeks/cms', 'New passwords do not match'));
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function changePassword()
    {
        if ($this->validate() && $this->new_password == $this->new_password_confirm && $this->new_password) {
            $this->user->setPassword($this->new_password);
            return $this->user->save(false);
        } else {
            return false;
        }
    }


}
