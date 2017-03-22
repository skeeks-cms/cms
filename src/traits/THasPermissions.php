<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */
namespace skeeks\cms\traits;
use skeeks\cms\rbac\CmsManager;
use yii\base\Model;

/**
 * @property array $permissionNames;
 * @property bool $isAllow;
 *
 * Class THasPermissions
 * @package skeeks\cms\traits
 */
trait THasPermissions
{
    /**
     * @var string
     */
    protected $_permissionNames = null;

    /**
     * @return array
     */
    public function getPermissionNames()
    {
        return $this->_permissionNames;
    }

    /**
     * @param array $permissionNames
     * @return $this
     */
    public function setPermissionNames(array $permissionNames = null)
    {
        $this->_permissionNames = $permissionNames;
        return $this;
    }


    /**
     * @return bool
     */
    public function getIsAllow()
    {
        if ($this->permissionNames)
        {
            foreach ($this->permissionNames as $permissionName => $permissionLabel)
            {
                //Привилегия доступу к админке
                if (!$permission = \Yii::$app->authManager->getPermission($permissionName))
                {
                    $permission = \Yii::$app->authManager->createPermission($permissionName);
                    $permission->description = $permissionLabel;
                    \Yii::$app->authManager->add($permission);
                }

                if ($roleRoot = \Yii::$app->authManager->getRole(CmsManager::ROLE_ROOT))
                {
                    if (!\Yii::$app->authManager->hasChild($roleRoot, $permission))
                    {
                        \Yii::$app->authManager->addChild($roleRoot, $permission);
                    }
                }

                if (!\Yii::$app->user->can($permissionName))
                {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}