<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.02.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\components\Cms;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "cms_user_email".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $value
 * @property string $approved
 * @property string $def
 * @property string $approved_key
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property CmsUser $user
 */
class CmsUserEmail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_email}}';
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampBehavior::className()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_UPDATE, [$this, "beforeSaveEvent"]);
    }

    /**
     * @param $event
     */
    public function beforeSaveEvent($event)
    {
        if ($this->def == Cms::BOOL_N) {
            if ($this->user_id) {
                if (!static::find()->where(['def' => Cms::BOOL_Y])->andWhere([
                    '!=',
                    'id',
                    $this->id
                ])->andWhere(['user_id' => $this->user_id])->count()) {
                    $this->def = Cms::BOOL_Y;
                }
            }
        } else {
            if ($this->def == Cms::BOOL_Y) {
                if ($this->user_id) {
                    static::updateAll(['def' => Cms::BOOL_N], [
                        'and',
                        ['user_id' => $this->user_id],
                        ['!=', 'id', $this->id]
                    ]);
                }
            }
        }
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['value'], 'required'],
            [['value'], 'email'],
            [['value', 'approved_key'], 'string', 'max' => 255],
            [['approved', 'def'], 'string', 'max' => 1],
            [['value'], 'unique'],
            [['approved'], 'default', 'value' => Cms::BOOL_N],
            [['def'], 'default', 'value' => Cms::BOOL_N],
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'user_id' => Yii::t('skeeks/cms', 'User'),
            'value' => "Email",
            'approved' => \Yii::t('skeeks/cms', "Approved"),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'def' => Yii::t('skeeks/cms', 'Default'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
