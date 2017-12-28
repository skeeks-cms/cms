<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\HasStorageFile;
use Yii;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_lang}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $active
 * @property string $def
 * @property integer $priority
 * @property string $code
 * @property string $name
 * @property string $description
 * @property integer $image_id
 *
 * @property CmsSite[] $cmsSites
 * @property CmsStorageFile $image
 */
class CmsLang extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_lang}}';
    }

    public function init()
    {
        parent::init();

        $this->on(BaseActiveRecord::EVENT_BEFORE_INSERT, [$this, 'afterBeforeChecks']);
        $this->on(BaseActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'afterBeforeChecks']);
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::className() =>
                [
                    'class' => HasStorageFile::className(),
                    'fields' => ['image_id']
                ],
        ]);
    }

    /**
     * @param Event $e
     * @throws Exception
     */
    public function afterBeforeChecks(Event $e)
    {
        //Если этот элемент по умолчанию выбран, то все остальны нужно сбросить.
        if ($this->active != Cms::BOOL_Y) {
            $active = static::find()->where(['!=', 'id', $this->id])->active()->one();

            if (!$active) {
                $this->active = Cms::BOOL_Y;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'def' => Yii::t('skeeks/cms', 'Default'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'code' => Yii::t('skeeks/cms', 'Code'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'description' => Yii::t('skeeks/cms', 'Description'),
            'image_id' => Yii::t('skeeks/cms', 'Image'),
        ]);
    }

    public function attributeHints()
    {
        return array_merge(parent::attributeLabels(), [
            'active' => \Yii::t('skeeks/cms', 'On the site must be included at least one language'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['code', 'name'], 'required'],
            [['code'], 'validateCode'],
            [['active', 'def'], 'string', 'max' => 1],
            [['code'], 'string', 'max' => 5],
            [['name', 'description'], 'string', 'max' => 255],
            [['code'], 'unique'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            ['def', 'default', 'value' => Cms::BOOL_N],
            [['image_id'], 'safe'],

            [
                ['image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles' => 1,
                'maxSize' => 1024 * 1024 * 2,
                'minSize' => 1024,
            ],
        ]);
    }

    public function validateCode($attribute)
    {
        if (!preg_match('/^[a-zA-Z]{1}[a-zA-Z0-9-]{1,255}$/', $this->$attribute)) {
            $this->addError($attribute, \Yii::t('skeeks/cms',
                'Use only letters of the alphabet in lower or upper case and numbers, the first character of the letter (Example {code})',
                ['code' => 'code1']));
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSites()
    {
        return $this->hasMany(CmsSite::className(), ['lang_code' => 'code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'image_id']);
    }
}