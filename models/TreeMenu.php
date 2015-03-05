<?php
/**
 * TreeMenu
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasPageOptions;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\traits\TreeBehaviorTrait;
use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\db\ActiveQuery;

/**
 *
 * @property string $type
 *
 * Class Tree
 * @package skeeks\cms\models
 */
class TreeMenu extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree_menu}}';
    }


    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['name', 'description'];
        $scenarios['update'] = ['name', 'description'];
        return $scenarios;
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
        ]);
    }



    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name'], 'required'],
            [['name'], 'unique'],
            [['name', 'description'], 'string'],
        ]);
    }

}
