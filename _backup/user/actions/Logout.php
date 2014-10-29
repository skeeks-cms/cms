<?php
/**
 * Logout
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\user\actions;

use Yii;
use yii\base\Action;

/**
 * Class Logout
 * @package skeeks\modules\cms\user\actions
 */
class Logout extends Action
{
    public function run()
    {
        Yii::$app->user->logout();
        return Yii::$app->getResponse()->redirect(Yii::$app->getUser()->getReturnUrl());
    }
}