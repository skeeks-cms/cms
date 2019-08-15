<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.10.2015
 */

namespace skeeks\cms\filters;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\User;

/**
 * Class AdminAccessControl
 * @package skeeks\cms\modules\admin\filters
 */
class CmsAccessControl extends \yii\filters\AccessControl
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
        $rr = new RequestResponse();

        if ($user->getIsGuest()) {
            $authUrl = UrlHelper::construct(["/cms/auth/login"])->setCurrentRef()->createAbsoluteUrl();

            if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
                \Yii::$app->getResponse()->redirect($authUrl);
                $rr->redirect = $authUrl;
                return (array)$rr;
            } else {
                \Yii::$app->response->redirect($authUrl);
                \Yii::$app->end();
            }

        } else {
            throw new ForbiddenHttpException(\Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
