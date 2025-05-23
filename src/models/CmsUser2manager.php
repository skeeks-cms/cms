<?php

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int     $id
 * @property int     $created_by
 * @property int     $updated_by
 * @property int     $created_at
 * @property int     $updated_at
 * @property int     $client_id Клиент
 * @property int     $worker_id Сотрудник
 *
 * @property CmsUser $client
 * @property CmsUser $worker
 */
class CmsUser2manager extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_user2manager}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['client_id', 'worker_id'], 'integer'],
            [['client_id', 'worker_id'], 'required'],
            [['client_id', 'worker_id'], 'unique', 'targetAttribute' => ['client_id', 'worker_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'worker_id' => Yii::t('app', 'Сотрудник'),
            'client_id' => Yii::t('app', 'Клиент'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        $userClass = \Yii::$app->user->identityClass;
        return $this->hasOne($userClass, ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        $userClass = \Yii::$app->user->identityClass;
        return $this->hasOne($userClass, ['id' => 'client_id']);
    }
}