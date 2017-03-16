<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */
namespace skeeks\cms;

/**
 * @property $permissionNames;
 * @property $permissionName;
 *
 * Interface IHasPermissions
 * @package skeeks\cms
 */
interface IHasPermissions
{
    /**
     * @return array
     */
    public function getPermissionNames();

    /**
     * @return string
     */
    public function getPermissionName();
}