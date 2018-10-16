<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

use skeeks\cms\models\behaviors\HasTableCache;
use skeeks\cms\models\CmsUser;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\traits\TActiveRecord;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * @method string getTableCacheTag()
 *
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property CmsUser $createdBy
 * @property CmsUser $updatedBy
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    use TActiveRecord;
    
    /**
     * @var array
     */
    public $raw_row = [];
    /**
     * @return CmsActiveQuery
     */
    public static function find()
    {
        if (self::getTableSchema()->getColumn('is_active')) {
            return new CmsActiveQuery(get_called_class(), ['is_active' => true]);
        }

        return new CmsActiveQuery(get_called_class(), ['is_active' => false]);
    }

    /** @inheritdoc */
    public static function populateRecord($record, $row)
    {
        /** @var static $record */
        $record->raw_row = $row;
        return parent::populateRecord($record, $row);
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            BlameableBehavior::className() => [
                'class' => BlameableBehavior::className(),
                'value' => function ($event) {
                    if (\Yii::$app instanceof \yii\console\Application) {
                        return null;
                    } else {
                        $user = Yii::$app->get('user', false);
                        return $user && !$user->isGuest ? $user->id : null;
                    }
                },
            ],
            TimestampBehavior::className() => [
                'class' => TimestampBehavior::className(),
            ],

            HasTableCache::className() => [
                'class' => HasTableCache::className(),
                'cache' => \Yii::$app->cache,
            ],
        ]);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'created_by']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'updated_by']);
    }
    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id'         => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
        ];
    }
    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'id'], 'integer'],
        ];
    }
}