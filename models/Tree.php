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

use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class Publication
 * @method ActiveQuery findRoots()
 *
 * @package skeeks\cms\models
 */
class Tree extends PageAdvanced
{
    public function behaviors()
    {
        //$behaviors = parent::behaviors();

        //SeoPageName::className()

        return array_merge(parent::behaviors(), [
            TreeBehavior::className()
        ]);
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree}}';
    }

}
