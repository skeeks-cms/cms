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

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\User;
use Yii;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
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
            TimestampBehavior::className(),
        ]);
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
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function findCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function findUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }


    /**
     * @return null|User
     */
    public function fetchCreatedBy()
    {
        return $this->findCreatedBy()->one();
    }

    /**
     * @return null|User
     */
    public function fetchUpdatedBy()
    {
        return $this->findUpdatedBy()->one();
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
        ];
    }
}