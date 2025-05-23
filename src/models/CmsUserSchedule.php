<?php

namespace skeeks\cms\models;

use common\models\User;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\queries\CmsUserScheduleQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%crm_schedule}}".
 *
 * @property int          $id
 * @property int          $cms_user_id Пользователь
 * @property int          $start_at Начало работы
 * @property int|null     $end_at Завершение работы
 *
 * @property CmsUser|User $cmsUser
 * 
 * @property int          $duration Продолжительность промежутка в понятном написании
 * @property string       $durationAsText Продолжительность промежутка в понятном написании
 */
class CmsUserSchedule extends \skeeks\cms\base\ActiveRecord
    //implements CrmScheduleInterface
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_user_schedule}}';
    }

    public function init()
    {
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cms_user_id', 'start_at'], 'required'],
            [['cms_user_id'], 'integer'],
            [['end_at'], 'safe'],
            [['cms_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::class, 'targetAttribute' => ['cms_user_id' => 'id']],

            [
                ['end_at'],
                function () {
                    if ($this->end_at && $this->start_at >= $this->end_at) {
                        $this->addError('end_at', 'Время начала работы должно быть меньше времени завершения.');
                        return false;
                    }
                    
                    if ($this->cmsUser->getExecutorTasks()->statusInWork()->one()) {
                        $this->addError('end_at', 'Для завершения работы, необходимо остановить задачу.');
                        return false;
                    }

                    //Ищем логи рабочего времени по задачам

                    /*if ($crmSchedules = static::find()->user($this->user)
                        ->andWhere(['>=', 'end_at', $this->end_at])
                        ->all()
                    ) {
                        $this->addError('end_at', 'В этом временном промежутке у вас были запущены задачи: '.
                            implode(",", ArrayHelper::map($crmSchedules, 'crm_task_id', 'crm_task_id'))
                        );
                        return false;
                    }*/
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'cms_user_id' => 'Пользователь',
            'start_at'    => 'Начало работы',
            'end_at'      => 'Завершение работы',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'cms_user_id']);
    }

    /**
     * @return CmsUserScheduleQuery
     */
    public static function find()
    {
        return new CmsUserScheduleQuery(get_called_class());
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