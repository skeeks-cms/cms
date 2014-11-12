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
use skeeks\cms\models\behaviors\traits\TreeBehaviorTrait;
use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class Tree
 * @package skeeks\cms\models
 */
class Tree extends PageAdvanced
{
    use TreeBehaviorTrait;

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


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type' => Yii::t('app', 'Tree type'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type'], 'string'],
        ]);
    }

    /**
     * @return string
     */
    public function createUrl()
    {
        if ($this->getDir())
        {
            return  DIRECTORY_SEPARATOR . $this->getDir();
        } else
        {
            return  DIRECTORY_SEPARATOR;
        }
    }

    public function createAbsoluteUrl()
    {
        if ($this->getDir())
        {
            return  DIRECTORY_SEPARATOR . $this->getDir();
        } else
        {
            return  DIRECTORY_SEPARATOR;
        }
    }


    /**
     * Нода по умолчанию, задается для всех сайтов проекта.
     * @return static
     */
    static public function findDefaultRoot()
    {
        return self::find()->where(['main_root' => 1])->one();
    }
}
