<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\components\Cms;
use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasTrees;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\behaviors\traits\HasTreesTrait;
use skeeks\cms\relatedProperties\models\RelatedElementModel;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use skeeks\modules\cms\user\models\User;
use skeeks\sx\String;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\ErrorHandler;

/**
 * This is the model class for table "{{%cms_content_element}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $published_at
 * @property integer $published_to
 * @property integer $priority
 * @property string $active
 * @property string $name
 * @property string $code
 * @property string $description_short
 * @property string $description_full
 * @property string $files
 * @property integer $content_id
 * @property integer $tree_id
 * @property integer $show_counter
 * @property integer $show_counter_start
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 *
 * @property string $absoluteUrl
 * @property string $url
 *
 * @property CmsContent $cmsContent
 * @property CmsTree $tree

 * @property CmsContentElementProperty[]    relatedElementProperties
 * @property CmsContentProperty[]           relatedProperties
 */
class CmsContentElement extends RelatedElementModel
{
    use \skeeks\cms\models\behaviors\traits\HasFiles;
    use HasRelatedPropertiesTrait;
    use HasTreesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_element}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampPublishedBehavior::className() => TimestampPublishedBehavior::className(),
            HasFiles::className() => HasFiles::className(),

            HasRelatedProperties::className() =>
            [
                'class'                             => HasRelatedProperties::className(),
                'relatedElementPropertyClassName'   => CmsContentElementProperty::className(),
                'relatedPropertyClassName'          => CmsContentProperty::className(),
            ],

            HasTrees::className() =>
            [
                'class'                             => HasTrees::className(),
                'elementTreesClassName'             => CmsContentElementTree::className(),
            ],

            SeoPageName::className() =>
            [
                'class'                             => SeoPageName::className(),
                'generatedAttribute'                => 'code',
            ]
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'published_at' => Yii::t('app', 'Published At'),
            'published_to' => Yii::t('app', 'Published To'),
            'priority' => Yii::t('app', 'Priority'),
            'active' => Yii::t('app', 'Active'),
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code'),
            'description_short' => Yii::t('app', 'Description Short'),
            'description_full' => Yii::t('app', 'Description Full'),
            'files' => Yii::t('app', 'Files'),
            'content_id' => Yii::t('app', 'Content ID'),
            'tree_id' => Yii::t('app', 'Tree ID'),
            'show_counter' => Yii::t('app', 'Show Counter'),
            'show_counter_start' => Yii::t('app', 'Show Counter Start'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
            'meta_description' => Yii::t('app', 'Meta Description'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'published_at', 'published_to', 'priority', 'content_id', 'tree_id', 'show_counter', 'show_counter_start'], 'integer'],
            [['name'], 'required'],
            [['files'], 'safe'],
            [['description_short', 'description_full'], 'string'],
            [['active'], 'string', 'max' => 1],
            [['name', 'code'], 'string', 'max' => 255],
            [['content_id', 'code'], 'unique', 'targetAttribute' => ['content_id', 'code'], 'message' => 'Для данного контента этот код уже занят.'],
            [['tree_id', 'code'], 'unique', 'targetAttribute' => ['tree_id', 'code'], 'message' => 'Для данного раздела этот код уже занят.'],
            [['treeIds'], 'safe'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContent()
    {
        return $this->hasOne(CmsContent::className(), ['id' => 'content_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTree()
    {
        return $this->hasOne(Tree::className(), ['id' => 'tree_id']);
    }

    /**
     *
     * Все возможные свойства связанные с моделью
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRelatedProperties()
    {
        return $this->cmsContent->cmsContentProperties;
    }





    /**
     * @return string
     */
    public function getAbsoluteUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return Url::to('cms/content-element/view', [
            'id'    => $this->id,
            'code'  => $this->code,
        ]);
    }
}
