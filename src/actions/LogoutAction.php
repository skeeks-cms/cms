<?php
/**
 * LogoutAction
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 05.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\actions;

use skeeks\cms\helpers\UrlHelper;
use Yii;
use yii\base\Action;

/**
 * Class ErrorAction
 * @package skeeks\cms\actions
 */
class LogoutAction extends Action
{
    /**
     * @return static
     */
    public function run()
    {
        Yii::$app->user->logout();
        if ($ref = UrlHelper::getCurrent()->getRef()) {
            return Yii::$app->getResponse()->redirect($ref);
        } else {
            return Yii::$app->getResponse()->redirect(Yii::$app->getUser()->getReturnUrl());
        }
    }
}
