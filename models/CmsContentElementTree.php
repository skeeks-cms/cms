<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\db\BaseActiveRecord;
use yii\widgets\ActiveForm;

/**
 * This is the model class for table "{{%cms_content_element_tree}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $element_id
 * @property integer $tree_id
 *
 * @property CmsContentElement $element
 * @property CmsTree $tree
 */
class CmsContentElementTree extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_element_tree}}';
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
            'element_id' => Yii::t('app', 'Element ID'),
            'tree_id' => Yii::t('app', 'Tree ID'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'element_id', 'tree_id'], 'integer'],
            [['element_id', 'tree_id'], 'required'],
            [['element_id', 'tree_id'], 'unique', 'targetAttribute' => ['element_id', 'tree_id'], 'message' => 'The combination of Element ID and Tree ID has already been taken.']
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getElement()
    {
        return $this->hasOne(CmsContentElement::className(), ['id' => 'element_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTree()
    {
        return $this->hasOne(Tree::className(), ['id' => 'tree_id']);
    }
}