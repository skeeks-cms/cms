<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms;

/**
 * @property $permissionName;
 *
 * Interface IHasPermission
 * @package skeeks\cms
 */
interface IHasPermission
{
    /**
     * @return string
     */
    public function getPermissionName();
}