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

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\CanBeLinkedToModel;
use skeeks\cms\models\behaviors\CanBeLinkedToTree;
use skeeks\cms\models\behaviors\HasAdultStatus;
use skeeks\cms\models\behaviors\HasPageOptions;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use Yii;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class Publication extends PageAdvanced
{
    public $viewPageTemplate = "cms/publication/view";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_publication}}';
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            CanBeLinkedToModel::className(),
            CanBeLinkedToTree::className(),
            TimestampPublishedBehavior::className() => TimestampPublishedBehavior::className(),
            HasPageOptions::className() => HasPageOptions::className(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type'          => Yii::t('app', 'Publication type'),
            'tree_ids'      => Yii::t('app', 'Разделы'),
            'page_options'  => Yii::t('app', 'Дополнительные свойства'),
            'published_at'  => Yii::t('app', 'Дата публикации'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type'], 'string'],
            [['published_at'], 'integer'],
            [['tree_ids', 'page_options', 'multiPageOptions'], 'safe'],
        ]);
    }

    /**
     * @return null|ModelType
     */
    public function getType()
    {
        if ($this->type)
        {
            return \Yii::$app->registeredModels->getDescriptor($this)->getTypes()->getComponent($this->type);
        }

        return null;
    }

}
