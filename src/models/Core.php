<?php
/**
 * Базовая модель содержит поведения пользователей, кто когда обновил, и создал сущьность
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasTableCache;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use Yii;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * @method string getTableCacheTag()
 *
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User $createdBy
 * @property User $updatedBy
 *
 * Class Core
 * @package skeeks\cms\base\models
 */
abstract class Core extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            BlameableBehavior::className() =>
            [
                'class' => BlameableBehavior::className(),
                'value' => function($event)
                {
                    if (\Yii::$app instanceof \yii\console\Application)
                    {
                        return null;
                    } else
                    {
                        $user = Yii::$app->get('user', false);
                        return $user && !$user->isGuest ? $user->id : null;
                    }
                },
            ],
            TimestampBehavior::className() =>
            [
                'class' => TimestampBehavior::className(),
                /*'value' => function()
                {
                    return date('U');
                },*/
            ],

            HasTableCache::className() =>
            [
                'class' => HasTableCache::className(),
                'cache' => \Yii::$app->cache
            ]
        ]);
    }

    /**
     * @return CmsActiveQuery
     */
    public static function find()
    {
        return new CmsActiveQuery(get_called_class());
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