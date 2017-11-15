<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms;

/**
 * @property $url;
 *
 * Interface IHasUrl
 * @package skeeks\cms
 */
interface IHasUrl
{
    /**
     * @return string
     */
    public function getUrl();
}