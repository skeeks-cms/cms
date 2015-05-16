<?php
/**
 * Infoblock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\modules\cms\user\models\User;
use Yii;

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
 *
 * @property CmsContent $content
 * @property User $createdBy
 * @property Tree $tree
 * @property User $updatedBy
 */
class CmsContentElement extends Core
{
    use \skeeks\cms\models\behaviors\traits\HasFiles;

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
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios['update'] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'published_at', 'published_to', 'priority', 'content_id', 'tree_id', 'show_counter', 'show_counter_start'], 'integer'],
            [['name'], 'required'],
            [['description_short', 'description_full', 'files'], 'string'],
            [['active'], 'string', 'max' => 1],
            [['name', 'code'], 'string', 'max' => 255],
            [['content_id', 'code'], 'unique', 'targetAttribute' => ['content_id', 'code'], 'message' => 'The combination of Code and Content ID has already been taken.'],
            [['tree_id', 'code'], 'unique', 'targetAttribute' => ['tree_id', 'code'], 'message' => 'The combination of Code and Tree ID has already been taken.']
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContent()
    {
        return $this->hasOne(CmsContent::className(), ['id' => 'content_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTree()
    {
        return $this->hasOne(Tree::className(), ['id' => 'tree_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }
}