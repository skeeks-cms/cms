<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms\traits;

use skeeks\cms\rbac\CmsManager;
use yii\base\InvalidConfigException;
use yii\web\Application;

/**
 * @property callable|null $accessCallback;
 * @property string|null $permissionName;
 * @property array|null $permissionNames;
 * @property bool $isAllow;
 *
 * Class THasPermissions
 * @package skeeks\cms\traits
 */
trait THasPermissions
{
    /**
     * @var array
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
     * @var callable|null|boolean
     */
    protected $_accessCallback = null;

    /**
     * @return callable|null|boolean
     */
    public function getAccessCallback()
    {
        return $this->_accessCallback;
    }

    /**
     * @param null|callable|boolean $accessCallback
     *
     * @return $this
     */
    public function setAccessCallback($accessCallback = null)
    {
        if ($accessCallback !== null && !is_callable($accessCallback) && !is_bool($accessCallback)) {
            throw new InvalidConfigException('accessCallback must be callable or null or boolean');
        }
        $this->_accessCallback = $accessCallback;
        return $this;
    }



    /**
     * @var string
     * @deprecated
     */
    protected $_permissionName = null;

    /**
     * @return string
     * @deprecated
     */
    public function getPermissionName()
    {
        return $this->_permissionName;
    }

    /**
     * @param string|null $permissionName
     * @return $this
     * @deprecated
     */
    public function setPermissionName($permissionName = null)
    {
        $this->_permissionName = $permissionName;
        return $this;
    }


    /**
     * @return bool
     */
    public function getIsAllow()
    {
        if ($this->accessCallback && is_callable($this->accessCallback)) {
            $callback = $this->accessCallback;
            return (bool)call_user_func($callback, $this);
        }

        if ($this->accessCallback && is_bool($this->accessCallback)) {
            return (bool)$this->accessCallback;
        }

        return $this->_isAllow();
    }

    /**
     * @return bool
     */
    protected function _isAllow()
    {
        if ($this->permissionNames) {
            foreach ($this->permissionNames as $permissionName => $permissionLabel) {
                //Привилегия доступу к админке
                if (!$permission = \Yii::$app->authManager->getPermission($permissionName)) {
                    $permission = \Yii::$app->authManager->createPermission($permissionName);
                    $permission->description = $permissionLabel;
                    \Yii::$app->authManager->add($permission);
                }
                if ($roleRoot = \Yii::$app->authManager->getRole(CmsManager::ROLE_ROOT)) {
                    if (!\Yii::$app->authManager->hasChild($roleRoot, $permission)) {
                        \Yii::$app->authManager->addChild($roleRoot, $permission);
                    }
                }
                if (\Yii::$app instanceof Application && !\Yii::$app->user->can($permissionName)) {
                    return false;
                }
            }
        }

        return true;
    }
}