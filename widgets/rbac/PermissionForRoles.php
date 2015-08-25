<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.08.2015
 */
namespace skeeks\cms\widgets\rbac;
use skeeks\cms\components\Cms;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\rbac\Permission;

/**
 * @property Permission $permission
 * @property array $permissionRoles
 *
 * Class AssignmentPrivilegesForRole
 * @package skeeks\cms\widgets\roles
 */
class PermissionForRoles extends Widget
{
    /**
     * @var string Привилегия которую необходимо назначать, и настраивать.
     */
    public $permissionName        = "";
    public $label                 = "";

    public function init()
    {
        parent::init();
    }

    public function run()
    {
        return $this->render('permission-for-roles', [
            'widget' => $this,
        ]);
    }

    /**
     * @return string
     */
    public function getClientOptionsJson()
    {
        return Json::encode([
            'id'                                => $this->id,
            'permissionName'                    => $this->permissionName,
            'backend'                           => Url::to('/cms/rbac/permission-for-role'),
        ]);
    }

    /**
     * @return \yii\rbac\Permission
     */
    public function getPermission()
    {
        return \Yii::$app->authManager->getPermission($this->permissionName);
    }


    /**
     * @return array
     */
    public function getPermissionRoles()
    {
        $result = [];

        if ($roles = \Yii::$app->authManager->getRoles())
        {
            foreach ($roles as $role)
            {
                //Если у роли есть это разрешение
                if (\Yii::$app->authManager->hasChild($role, $this->permission))
                {
                    $result[] = $role->name;
                }
            }
        }

        return $result;
    }
}