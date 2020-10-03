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
 * @property string|null $permissionName;
 *
 * Trait THasPermissions
 * @package skeeks\cms\traits
 */
trait THasPermission
{
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
}