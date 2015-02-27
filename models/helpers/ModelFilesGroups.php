<?php
/**
 * ModelFilesGroups
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\helpers;
use skeeks\cms\components\CollectionComponents;

/**
 *
 * @method ModelFilesGroup[]   all()
 * @method ModelFilesGroup[]   getComponents()
 * @method ModelFilesGroup     getComponent($id)
 *
 * Class FilesGroups
 * @package skeeks\cms\models\helpers
 */
class ModelFilesGroups extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\helpers\ModelFilesGroup';
}