<?php
/**
 * Валидация email пользователя
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\validators\user;

use common\models\User;
use skeeks\cms\models\user\UserEmail;
use skeeks\sx\validate\Validate;
use skeeks\sx\validators\Validator;

/**
 * Class UserEmailValidator
 * @package skeeks\cms\validators\user
 */
class UserEmailValidator
    extends Validator
{
    /**
     * @param User $user
     * @return \skeeks\sx\validate\Result
     */
    public function validate($user)
    {
        //Если пользователь новый
        if ($user->isNewRecord)
        {
            $userEmail = UserEmail::find()->where(['value' => $user->email])->one();

            if ($userEmail)
            {
                if ($userEmail->user_id != $user->id)
                {
                    return $this->_bad("Email «{$user->email}» уже занят другим пользователем.");
                }
            }
        } else
        {
            //Email не изменился
            if ($user->oldAttributes['email'] == $user->email)
            {
                return $this->_ok();
            } else
            {
                $userEmail = UserEmail::find()->where(['value' => $user->email])->one();

                if ($userEmail)
                {
                    if ($userEmail->user_id != $user->id)
                    {
                        return $this->_bad("Email «{$user->email}» уже занят другим пользователем.");
                    }
                }
            }
        }

        return $this->_ok();
    }


}