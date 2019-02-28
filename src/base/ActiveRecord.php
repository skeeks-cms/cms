<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

use common\models\User;
use skeeks\cms\models\behaviors\HasTableCache;
use skeeks\cms\models\CmsUser;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\traits\TActiveRecord;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * @method string getTableCacheTag()
 *
 * @property integer      $created_by
 * @property integer      $updated_by
 * @property integer      $created_at
 * @property integer      $updated_at
 *
 * @property CmsUser|User $createdBy
 * @property CmsUser|User $updatedBy
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

    static public function safeGetTableSchema()
    {
        try {
            return self::getTableSchema();
        } catch (\Exception $exceptione) {
            return false;
        }
    }
    /**
     * @return CmsActiveQuery
     */
    public static function find()
    {
        if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('is_active')) {
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
        $result = array_merge(parent::behaviors(), [
            HasTableCache::class => [
                'class' => HasTableCache::class,
                'cache' => \Yii::$app->cache,
            ],
        ]);

        try {
            if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('created_by') && self::safeGetTableSchema()->getColumn('updated_by')) {
                $result[BlameableBehavior::class] = [
                    'class' => BlameableBehavior::class,
                    'value' => function ($event) {
                        if (\Yii::$app instanceof \yii\console\Application) {
                            return null;
                        } else {
                            $user = Yii::$app->get('user', false);
                            return $user && !$user->isGuest ? $user->id : null;
                        }
                    },
                ];
            }

            if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('created_at') && self::safeGetTableSchema()->getColumn('updated_at')) {
                $result[TimestampBehavior::class] = [
                    'class' => TimestampBehavior::class,
                    /*'value' => function () {
                        return date('U');
                    },*/
                ];
            } elseif (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('created_at')) {
                $result[TimestampBehavior::class] = [
                    'class'      => TimestampBehavior::class,
                    'attributes' => [
                        ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                        ActiveRecord::EVENT_BEFORE_UPDATE => [],
                    ],
                ];
            } elseif (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('updated_at')) {
                $result[TimestampBehavior::class] = [
                    'class'      => TimestampBehavior::class,
                    'attributes' => [
                        ActiveRecord::EVENT_BEFORE_INSERT => ['updated_at'],
                        ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                    ],
                ];
            }

        } catch (InvalidConfigException $e) {

        }

        return $result;
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
        $result = [];


        if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('created_by')) {
            $result[] = ['created_by', 'integer'];
        }
        if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('updated_by')) {
            $result[] = ['updated_by', 'integer'];
        }
        if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('created_at')) {
            $result[] = ['created_at', 'integer'];
        }
        if (self::safeGetTableSchema() && self::safeGetTableSchema()->getColumn('updated_at')) {
            $result[] = ['updated_at', 'integer'];
        }

        return $result;
        /*
         return [
             [[
                 'created_by',
                 'updated_by',
                 'created_at',
                 'updated_at',
                 'id'
             ], 'integer'],
         ];*/
    }
}