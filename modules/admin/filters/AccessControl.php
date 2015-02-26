<?php

/**
 * AccessControl
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 05.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\filters;

use skeeks\cms\App;

use skeeks\cms\helpers\UrlHelper;
use yii\web\User;
use yii\web\ForbiddenHttpException;

/**
 * Class AccessControl
 * @package skeeks\cms\modules\admin\filters
 */
class AccessControl extends \yii\filters\AccessControl
{
    /**
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403 HTTP exception will be thrown.
     * @param User $user the current user
     * @throws ForbiddenHttpException if the user is already logged in.
     */
    protected function denyAccess($user)
    {
        if ($user->getIsGuest())
        {
            \Yii::$app->getResponse()->redirect(
                UrlHelper::construct("admin/auth")->setCurrentRef()->enableAdmin()->createUrl()
            );
        } else
        {

            throw new ForbiddenHttpException(\Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
