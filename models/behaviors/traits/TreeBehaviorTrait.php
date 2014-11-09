<?php
/**
 * TreeBehaviorTrait
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\traits;
use skeeks\cms\base\behaviors\ActiveRecord;
use skeeks\cms\models\Tree;

/**
 * @method ActiveRecord         findRoots()
 * @method ActiveRecord         findChildrens()
 * @method Tree                 findParent()
 * @method ActiveRecord         processAddNode(ActiveRecord $target)
 * @method ActiveRecord         generateSeoPageName()
 * @method bool                 isRoot()
 *
 * @property string $pidAttrName
 * @property string $pidsAttrName
 * @property string $levelAttrName
 * @property string $dirAttrName
 * @property string $pageAttrName
 * @property string $nameAttrName
 * @property string $hasChildrenAttrName
 *
 * Class TreeBehaviorTrait
 * @package skeeks\cms\models\behaviors\traits
 */
trait TreeBehaviorTrait
{}