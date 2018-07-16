<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms;

/**
 * @property string $name;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
interface IHasName
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param string|array $name
     * @return mixed
     */
    public function setName($name);
}