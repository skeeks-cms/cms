<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\queries\CmsTaskScheduleQuery;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "cms_contractor".
 *
 * @property int     $id
 * @property int     $created_at
 * @property int     $created_by
 * @property int     $cms_user_id Пользователь
 * @property int     $cms_task_id Задача
 * @property string  $start_at Начало работы
 * @property string  $end_at Завершение работы
 *
 * @property CmsUser $cmsUser
 * @property CmsTask $cmsTask
 */
class CmsTaskSchedule extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_task_schedule';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_at', 'created_by', 'cms_task_id', 'cms_user_id'], 'integer'],
            [['cms_task_id', 'cms_user_id', 'start_at'], 'required'],

            [
                ['start_at'],
                function () {
                    if ($this->isNewRecord) {
                        if (static::find()
                            ->createdBy()
                            //->task($this->cms_task_id)
                            ->notEnd()->one()) {
                        /*if ($this->cmsTask->notEndCrmTaskSchedule) {*/
                            $this->addError('end_time', 'У вас уже запущена задача. Для начала сотановите ее.');
                            return false;
                        }
                    }
                },
            ],

            [
                ['end_at'],
                function () {
                    if ($this->end_at && $this->start_at >= $this->end_at) {
                        $this->addError('end_at', 'Время начала работы должно быть меньше времени завершения.');
                        return false;
                    }
                },
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_task_id' => 'Задача',
            'cms_user_id' => 'Пользователь',
            'start_at'    => 'Начало работы',
            'end_at'      => 'Завершение работы',
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTask()
    {
        return $this->hasOne(CmsTask::class, ['id' => 'cms_task_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'cms_user_id']);
    }
    
    /**
     * @return CmsTaskScheduleQuery
     */
    public static function find()
    {
        return new CmsTaskScheduleQuery(get_called_class());
    }
    
    
    /**
     * Длительность промежутка в секундах
     *
     * @return int
     */
    public function getDuration()
    {
        if ($this->end_at) {
            $end = $this->end_at;
        } else {
            $end = time();
        }

        return $end - $this->start_at;
    }

    /**
     * Длительность промежутка в понятном названии
     *
     * @return string
     */
    public function getDurationAsText()
    {
        return CmsScheduleHelper::durationAsText($this->duration);
    }
}