<?php

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int           $id
 * @property int           $created_by
 * @property int           $updated_by
 * @property int           $created_at
 * @property int           $updated_at
 * @property int           $cms_department_id Отдел
 * @property int           $worker_id Сотрудник
 *
 * @property CmsUser       $worker
 * @property CmsDepartment $department
 */
class CmsDepartment2worker extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_department2worker}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_department_id', 'worker_id'], 'integer'],
            [['cms_department_id', 'worker_id'], 'required'],
            [['cms_department_id', 'worker_id'], 'unique', 'targetAttribute' => ['cms_department_id', 'worker_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_department_id' => Yii::t('app', 'Отдел'),
            'worker_id'         => Yii::t('app', 'Сотрудник'),
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
    public function getDepartment()
    {
        return $this->hasOne(CmsDepartment::class, ['id' => 'cms_department_id']);
    }
}