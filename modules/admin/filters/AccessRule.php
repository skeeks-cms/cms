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
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\base\Action;
use yii\behaviors\BlameableBehavior;
use yii\web\Request;
use yii\web\User;
use yii\web\ForbiddenHttpException;

/**
 * Class AccessControl
 * @package skeeks\cms\modules\admin\filters
 */
class AccessRule extends \yii\filters\AccessRule
{
    /**
     * Checks whether the Web user is allowed to perform the specified action.
     * @param Action $action the action to be performed
     * @param User $user the user object
     * @param Request $request
     * @return boolean|null true if the user is allowed, false if the user is denied, null if the rule does not apply to the user
     */
    public function allows($action, $user, $request)
    {
        if ($action->controller instanceof AdminModelEditorController)
        {

            if ($action->controller->getCurrentModel())
            {
                if (Validate::validate(new HasBehavior(BlameableBehavior::className()), $action->controller->getCurrentModel())->isValid())
                {
                    $acttionPermissionNameOwn = App::moduleAdmin()->getPermissionCode($action->controller->getUniqueId() . '/' . $action->id);
                    if ($permission = \Yii::$app->authManager->getPermission($acttionPermissionNameOwn))
                    {
                        if (!\Yii::$app->user->can($permission->name, [
                            'model' => $action->controller->getCurrentModel()
                        ])) {
                            return false;
                        }
                    }
                }
            }
        } else if ($action->controller instanceof AdminController)
        {

            //Смотрим зарегистрирована ли привилегия этого контроллера, если да то проверим ее
            $acttionPermissionName = App::moduleAdmin()->getPermissionCode($action->controller->getUniqueId() . '/' . $action->id);

            if ($permission = \Yii::$app->authManager->getPermission($acttionPermissionName))
            {
                if (!\Yii::$app->user->can($permission->name))
                {

                    return false;
                }

            }
        }

        return parent::allows($action, $user, $request);
    }

}
