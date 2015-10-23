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
use yii\db\ActiveQuery;

/**
 * @method ActiveQuery          findRoots()
 * @method ActiveQuery          findChildrens()
 * @method ActiveRecord         processAddNode(Tree $tree)
 * @method ActiveRecord         processCreateNode(Tree $tree)
 * @method ActiveRecord         generateSeoPageName()
 *
 * @property string $pidMainAttrName
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