<?php
/**
 * Вспомогательные иструменты
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\rbac\CmsManager;
use skeeks\sx\models\Ref;
use Yii;
use yii\web\Response;


/**
 * Class RbacController
 * @package skeeks\cms\controllers
 */
class RbacController extends Controller
{
    /**
     * Выбор файла
     * @return string
     */
    public function actionPermissionForRole()
    {
        $rr = new RequestResponse();

        if (!$permissionName = \Yii::$app->request->post('permissionName'))
        {
            $rr->success = false;
            $rr->message = "Некорректные параметры";
            return $rr;
        }

        $permission = \Yii::$app->authManager->getPermission($permissionName);
        if (!$permission)
        {
            $rr->success = false;
            $rr->message = "Привилегия не найдена";
            return $rr;
        }

        $rolesValues = (array) \Yii::$app->request->post('roles');
        $rolesValues[] = CmsManager::ROLE_ROOT; //у root пользователя нельзя отобрать права

        foreach (\Yii::$app->authManager->getRoles() as $role)
        {
            if (in_array($role->name, $rolesValues))
            {
                if (!\Yii::$app->authManager->hasChild($role, $permission))
                {
                    \Yii::$app->authManager->addChild($role, $permission);
                }
            } else
            {
                if (\Yii::$app->authManager->hasChild($role, $permission))
                {
                    \Yii::$app->authManager->removeChild($role, $permission);
                }
            }

            $rr->message = "Права доступа сохранены";
            $rr->success = true;
        }

        return $rr;
    }

}
