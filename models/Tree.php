<?php
/**
 * Publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class Publication
 * @method ActiveRecord         findRoots()
 * @method ActiveRecord         findChildrens()
 * @method Tree                 findParent()
 * @method ActiveRecord         processAddNode(ActiveRecord $target)
 *
 * @package skeeks\cms\models
 */
class Tree extends PageAdvanced
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $result = [];
        foreach ($behaviors as $key => $behavior)
        {
            if ($behavior != SeoPageName::className())
            {
                $result[$key] = $behavior;
            }
        }

        $result[] = TreeBehavior::className();
        return $result;
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree}}';
    }

}
