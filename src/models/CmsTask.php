<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\models\queries\CmsTaskQuery;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "cms_contractor".
 *
 * @property int                 $id
 *
 * @property int|null            $created_by Автор задачи
 * @property int|null            $created_at
 *
 * @property string              $name Название
 * @property string              $description Описание задачи
 *
 * @property int|null            $executor_id Исполнитель
 * @property int|null            $cms_project_id Проект
 * @property int|null            $cms_company_id Компания
 * @property int|null            $cms_user_id Клиент
 *
 * @property int|null            $plan_start_at Планируемое начало
 * @property int|null            $plan_end_at Планируемое завершение
 *
 * @property int|null            $plan_duration Длительность по плану в секундах
 * @property int|null            $fact_duration Фактическая длительность в секундах
 *
 * @property string              $status Статус задачи
 *
 * @property int                 $executor_sort Сортировка у исполнителя
 * @property int|null         $executor_end_at Ориентировочная дата исполнения задачи исполнителем
 *
 * @property CmsProject          $cmsProject
 * @property CmsCompany          $cmsCompany
 * @property CmsUser             $cmsUser
 * @property CmsUser             $executor
 *
 * @property CrmTaskSchedule[]   $schedules
 *
 * @property CrmSchedule|null    $notEndSchedule Не закрытый временной промежуток
 * @property CrmSchedule|null    $notEndScheduleByDate Не закрытый временной промежуток за сегодня
 * @property CrmSchedules[]|null $schedulesByDate Рабочие промежутки за сегодняшний день
 *
 *
 * @property string              $statusAsText
 * @property string              $statusAsIcon
 * @property string              $statusAsColor
 * @property string              $statusAsFeatureHint
 * @property string              $statusAsHint
 */
class CmsTask extends ActiveRecord
{
    use HasLogTrait;

    protected static $_isRecalculatingTasksPriority = false;

    const STATUS_NEW = 'new';
    const STATUS_ACCEPTED = 'accepted';

    const STATUS_IN_WORK = 'in_work';
    const STATUS_ON_PAUSE = 'on_pouse';
    const STATUS_ON_CHECK = 'on_check';

    const STATUS_CANCELED = 'canceled';
    const STATUS_READY = 'ready';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_task';
    }

    public function init()
    {
        //Уведомить исполнителя
        /*$this->on(self::EVENT_AFTER_INSERT, function($e) {
            if ($this->executor_id) {
                $notify = new CmsWebNotify();
                $notify->cms_user_id = $this->executor_id;
                $notify->name = "Вам поставлена новая задача";
                $notify->model_id = $this->id;
                $notify->model_code = $this->skeeksModelCode;
                $notify->save();
            }
        });*/

        $this->on(self::EVENT_BEFORE_DELETE, function () {
            if ($this->schedules) {
                throw new Exception("Нельзя удалить эту задачу, потому что по ней есть отработанное время.");
            }
        });

        return parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFileMulti::class => [
                'class'     => HasStorageFileMulti::class,
                'relations' => [
                    [
                        'relation' => 'files',
                        'property' => 'fileIds',
                    ],
                ],
            ],

            CmsLogBehavior::class => [
                'class'        => CmsLogBehavior::class,
                'no_log_fields' => [
                    'executor_sort',
                    'executor_end_at',
                ],
                'relation_map' => [
                    'cms_project_id' => 'cmsProject',
                    'cms_company_id' => 'cmsCompany',
                    'cms_user_id'    => 'cmsUser',
                    'executor_id'    => 'executor',
                    'status'         => 'statusAsText',
                ],
            ],
        ]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->plan_start_at && $this->plan_duration) {
            $this->plan_end_at = (int)$this->plan_start_at + (int)$this->plan_duration;
        } else {
            $this->plan_end_at = null;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (static::$_isRecalculatingTasksPriority) {
            return;
        }

        $recalculateAttributes = [
            'executor_id',
            'plan_duration',
            'status',
        ];
        $needRecalculate = $insert;
        foreach ($recalculateAttributes as $attribute) {
            if (array_key_exists($attribute, $changedAttributes)) {
                $needRecalculate = true;
                break;
            }
        }

        if (!$needRecalculate) {
            return;
        }

        $userIds = [];
        if ($this->executor_id) {
            $userIds[(int)$this->executor_id] = (int)$this->executor_id;
        }
        if (!empty($changedAttributes['executor_id'])) {
            $userIds[(int)$changedAttributes['executor_id']] = (int)$changedAttributes['executor_id'];
        }

        $activeStatuses = [
            self::STATUS_NEW,
            self::STATUS_IN_WORK,
            self::STATUS_ON_PAUSE,
            self::STATUS_ACCEPTED,
        ];

        if (($insert || array_key_exists('executor_id', $changedAttributes)) && $this->executor_id && in_array($this->status, $activeStatuses)) {
            $maxSort = (int)static::find()
                ->andWhere(['executor_id' => $this->executor_id])
                ->andWhere(['status' => $activeStatuses])
                ->andWhere(['!=', static::tableName().'.id', $this->id])
                ->max('executor_sort');
            static::updateAll(['executor_sort' => $maxSort + 100], ['id' => $this->id]);
        }

        foreach ($userIds as $userId) {
            $user = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $userId])->one();
            if ($user) {
                static::recalculateTasksPriority($user, []);
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'created_at', 'cms_project_id', 'executor_id', 'plan_start_at', 'plan_end_at'], 'integer'],
            [['executor_sort'], 'integer'],
            [['cms_company_id'], 'integer'],
            [['cms_user_id'], 'integer'],

            [['executor_end_at'], 'integer'],
            [['name', 'executor_id'], 'required'],

            [['description'], 'string'],

            [['status'], 'string'],

            [['plan_duration', 'fact_duration'], 'integer'],

            [['name'], 'string', 'max' => 255],

            ['plan_duration', 'default', 'value' => 60 * 15], //15 минут
            [
                'plan_start_at',
                function ($attribute) {
                    if (!$this->{$attribute}) {
                        return true;
                    }

                    if (!$this->isNewRecord && !$this->isAttributeChanged($attribute)) {
                        return true;
                    }

                    $minStartAt = time() + 30 * 60;
                    $minStartAt = $minStartAt - ($minStartAt % 60);
                    if ((int)$this->{$attribute} < $minStartAt) {
                        $this->addError($attribute, "Нельзя указать время начала задачи раньше чем через 30 минут.");
                        return false;
                    }

                    return true;
                },
            ],

            [
                'status',
                'default',
                'value' => function () {
                    //Если задавча ставится себе, то она сразу подтверждена


                    if ($this->executor_id == $this->created_by || !$this->created_by) {
                        return self::STATUS_ACCEPTED;
                    } else {
                        return self::STATUS_NEW;
                    }

                },
            ],

            [
                'status',
                function () {

                    if ($this->isNewRecord) {
                        if ($this->executor_id == $this->created_by || (!$this->created_by && $this->executor_id == \Yii::$app->user->identity->id)) {
                            $this->status = self::STATUS_ACCEPTED;
                        } else {
                            $this->status = self::STATUS_NEW;
                        }
                        return true;
                    }

                    //Если статус меняется
                    if (!$this->isNewRecord && $this->isAttributeChanged('status')) {

                        //Если принимается задача
                        if ($this->status == self::STATUS_ACCEPTED) {

                            if (!in_array($this->getOldAttribute('status'), [self::STATUS_NEW])) {
                                $this->addError('status', "Для того чтобы принять задачу, она должна быть в статусе новая");
                                return false;
                            }

                            if (\Yii::$app->user->id != $this->executor_id) {
                                $this->addError('status', "Принять задачу может только исполнитель");
                                return false;
                            }

                            return true;

                        } elseif ($this->status == self::STATUS_CANCELED) {

                            if (!in_array($this->getOldAttribute('status'), [self::STATUS_NEW, self::STATUS_ACCEPTED])) {
                                $this->addError('status', "Для того чтобы отменить задачу, она должна быть в статусе (новая, принята)");
                                return false;
                            }

                            if (\Yii::$app->user->id == $this->executor_id) {
                                return true;
                            }

                            if (\Yii::$app->user->id == $this->created_by) {
                                return true;
                            }

                            $this->addError('status', "Отменить задачу может только исполнитель или автор.");
                            return false;

                        } elseif ($this->status == self::STATUS_IN_WORK) {

                            if (!in_array($this->getOldAttribute('status'), [self::STATUS_ACCEPTED, self::STATUS_ON_PAUSE])) {
                                $this->addError('status', "Для того чтобы включить задачу, она должна быть в статусе (принята или на паузе)");
                                return false;
                            }

                            if (\Yii::$app->user->id != $this->executor_id) {
                                $this->addError('status', "Взять в работу задачу может только исполнитель.");
                                return false;
                            }

                            //Если пользователь сейчас не работает
                            if (!\Yii::$app->user->identity->isWorkingNow) {
                                $this->addError('status', "Для начала необходимо включить работу.");
                                return false;
                            }

                            if (\Yii::$app->user->identity->getExecutorTasks()->statusInWork()->one()) {
                                $this->addError('status', "У вас уже запущена другая задача.");
                                return false;
                            }

                            return true;
                        } elseif ($this->status == self::STATUS_ON_PAUSE) {

                            if (!in_array($this->getOldAttribute('status'), [self::STATUS_IN_WORK, self::STATUS_ON_CHECK, self::STATUS_READY, self::STATUS_CANCELED])) {
                                $this->addError('status', "Для того чтобы включить задачу, она должна быть в статусе (в работе)");
                                return false;
                            }

                            if (\Yii::$app->user->id != $this->executor_id && $this->getOldAttribute('status') == self::STATUS_IN_WORK) {
                                $this->addError('status', "Остановить задачу может только исполнитель.");
                                return false;
                            }

                            return true;
                        } elseif ($this->status == self::STATUS_ON_CHECK) {

                            if (!in_array($this->getOldAttribute('status'), [
                                self::STATUS_IN_WORK
                                //, self::STATUS_ON_PAUSE
                            ])) {
                                $this->addError('status', "Для того чтобы отправить задачу на проверку, она должна быть в статусе (в работе)");
                                return false;
                            }

                            if (\Yii::$app->user->id != $this->executor_id) {
                                $this->addError('status', "Может только исполнитель.");
                                return false;
                            }

                            return true;
                        } elseif ($this->status == self::STATUS_READY) {

                            if ($this->executor_id == $this->created_by) {
                                if (!in_array($this->getOldAttribute('status'), [self::STATUS_IN_WORK])) {
                                    $this->addError('status', "Для того чтобы сделать задачу готовой, она должна быть в статусе (в работе)");
                                    return false;
                                }
                            } else {
                                if (!in_array($this->getOldAttribute('status'), [self::STATUS_ON_CHECK])) {
                                    $this->addError('status', "Для того чтобы сделать задачу готовой, она должна быть в статусе (на проверке)");
                                    return false;
                                }
                            }


                            /*if (\Yii::$app->user->id != $this->executor_id) {
                                $this->addError('status', "Может только исполнитель.");
                                return false;
                            }*/

                            return true;
                        }

                    }


                    return true;
                },
            ],

            [['fileIds'], 'safe'],

            [
                ['fileIds'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                //'extensions'    => [''],
                'maxFiles'    => 50,
                'maxSize'     => 1024 * 1024 * 100,
                'minSize'     => 256,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_project_id' => 'Проект',
            'name'           => 'Название',
            'description'    => 'Описание задачи',

            'description' => 'Описание задачи',

            'cms_company_id' => 'Компания',
            'cms_user_id'    => 'Клиент',

            'plan_start_at' => 'Планируемое начало',
            'plan_end_at'   => 'Планируемое завершение',
            'plan_duration' => 'Длительность по плану',
            'fact_duration' => 'Фактическая длительность',

            'status' => 'Статус',

            'executor_id'   => 'Исполнитель',
            'executor_sort' => 'Приоритет исполнителя по задаче',
            'executor_end_at' => 'Ориентировочная дата исполнения задачи исполнителем',

            'fileIds' => "Файлы",
        ], [
            'fact_duration' => 'Длительность для отчета',
        ]);
    }


    protected $_file_ids = null;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(CmsStorageFile::class, ['id' => 'storage_file_id'])
            ->via('cmsTaskFiles')
            ->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTaskFiles()
    {
        return $this->hasMany(CmsTaskFile::className(), ['cms_task_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getFileIds()
    {
        if ($this->_file_ids !== null) {
            return $this->_file_ids;
        }

        if ($this->files) {
            return ArrayHelper::map($this->files, 'id', 'id');
        }

        return [];
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function setFileIds($ids)
    {
        $this->_file_ids = $ids;
        return $this;
    }


    static public function statuses($status = null)
    {
        $data = [
            self::STATUS_NEW      => "Новая",
            self::STATUS_ACCEPTED => "Принята",

            self::STATUS_CANCELED => "Отменена",
            self::STATUS_READY    => "Готова",
            self::STATUS_IN_WORK  => "В работе",
            self::STATUS_ON_CHECK => "На проверке",
            self::STATUS_ON_PAUSE => "На паузе",
        ];

        return ArrayHelper::getValue($data, $status, $data);
    }

    static public function statusesIcons($status = null)
    {
        $data = [
            self::STATUS_NEW      => "fas fa-plus",
            self::STATUS_ACCEPTED => "fas fa-anchor",

            self::STATUS_CANCELED => "fas fa-times-circle",
            self::STATUS_READY    => "fas fa-check",
            self::STATUS_IN_WORK  => "fas fa-play",
            self::STATUS_ON_CHECK => "fas fa-user-check",
            self::STATUS_ON_PAUSE => "fas fa-pause",
        ];

        return ArrayHelper::getValue($data, $status, $data);
    }

    /**
     * @param null $status
     * @return string|array
     */
    static public function statusesHints($status = null)
    {
        $data = [
            self::STATUS_NEW      => "Новая задача",
            self::STATUS_ACCEPTED => "Задача закреплена за исполнителем",

            self::STATUS_CANCELED => "Задача отменена",
            self::STATUS_READY    => "Задача готова",
            self::STATUS_IN_WORK  => "Задача сейчас в работе",
            self::STATUS_ON_CHECK => "Задача на проверке у автора или ответственного менеджера",
            self::STATUS_ON_PAUSE => "Задача на паузе",
        ];

        return ArrayHelper::getValue($data, $status, $data);
    }

    /**
     * @param null $status
     * @return string|array
     */
    static public function statusesFeatureHints($status = null)
    {
        $data = [
            self::STATUS_NEW      => "Новая задача",
            self::STATUS_ACCEPTED => "Задача будет закреплена за исполнителем, и он сможет взять ее в работу",

            self::STATUS_CANCELED => "Задача будет отменена",
            self::STATUS_READY    => "Задача будет готова",
            self::STATUS_IN_WORK  => "Начать работу по этой задаче. <br />Для этого вы должны быть включены в работу и у вас не должно быть запущено других задач",
            self::STATUS_ON_CHECK => "Отправить задачу на проверку автору или ответственному менеджеру",
            self::STATUS_ON_PAUSE => "Поставить задачу на паузу",
        ];

        return ArrayHelper::getValue($data, $status, $data);
    }

    /**
     * @return string
     */
    public function getStatusAsFeatureHint()
    {
        return (string)self::statusesFeatureHints($this->status);
    }

    /**
     * @return string
     */
    public function getStatusAsHint()
    {
        return (string)self::statusesHints($this->status);
    }

    /**
     * @param null $status
     * @return string|array
     */
    static public function statusesColors($status = null)
    {
        $data = [
            self::STATUS_NEW      => "g-bg-gray-light-v1",
            self::STATUS_ACCEPTED => "u-btn-purple",

            self::STATUS_CANCELED => "u-btn-darkred",
            self::STATUS_READY    => "u-btn-green",
            self::STATUS_IN_WORK  => "u-btn-teal",
            self::STATUS_ON_CHECK => "u-btn-cyan",
            self::STATUS_ON_PAUSE => "u-btn-orange",
        ];

        return ArrayHelper::getValue($data, $status, $data);
    }
    /**
     * @param null $status
     * @return string|array
     */
    static public function statusesSourceColors($status = null)
    {
        $data = [
            self::STATUS_NEW      => "#bbb",
            self::STATUS_ACCEPTED => "#9a69cb",

            self::STATUS_CANCELED => "u-btn-darkred",
            self::STATUS_READY    => "green",
            self::STATUS_IN_WORK  => "#18ba9b",
            self::STATUS_ON_CHECK => "#00bed6",
            self::STATUS_ON_PAUSE => "#e57d20",
        ];

        return ArrayHelper::getValue($data, $status, $data);
    }

    /**
     * @return string
     */
    public function getStatusAsText()
    {
        return ArrayHelper::getValue(self::statuses(), $this->status);
    }

    /**
     * @return string
     */
    public function getStatusAsIcon()
    {
        return (string)ArrayHelper::getValue(self::statusesIcons(), $this->status);
    }

    /**
     * @return string
     */
    public function getStatusAsColor()
    {
        return (string)ArrayHelper::getValue(self::statusesColors(), $this->status);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsProject()
    {
        return $this->hasOne(CmsProject::class, ['id' => 'cms_project_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExecutor()
    {
        return $this->hasOne(\Yii::$app->user->identityClass, ['id' => 'executor_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSchedules()
    {
        return $this->hasMany(CmsTaskSchedule::class, ['cms_task_id' => 'id'])->from(['schedules' => CmsTaskSchedule::tableName()]);;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSchedulesByDate($date = null)
    {
        if ($date === null) {
            $date = \Yii::$app->formatter->asDate(time(), "php:Y-m-d");
        }

        return $this->getSchedules()->andWhere(['date' => $date]);
    }

    /**
     * Не закрытый временной промежуток
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotEndSchedule()
    {
        $query = $this->getSchedules()->andWhere(['end_time' => null]);
        $query->multiple = false;
        return $query;
    }

    /**
     * Не закрытый временной промежуток сегодня
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotEndScheduleByDate($date = null)
    {
        if ($date === null) {
            $date = \Yii::$app->formatter->asDate(time(), "php:Y-m-d");
        }


        $query = $this->getSchedules()->andWhere(['end_time' => null])->andWhere(['date' => $date]);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return float|int
     */
    public function getPlanDurationSeconds()
    {
        return $this->plan_duration;
    }

    static public function recalculateTasksPriority(CmsUser $user, $sortTaskIds)
    {
        $currentUser = \Yii::$app->user->identity;
        /**
         * @var $task \skeeks\crm\models\CrmTask
         */
        $scheduleTotalTime = CmsTaskSchedule::find()->select([
            'SUM(end_at - start_at) as total_timestamp',
        ])->where([
            'cms_task_id' => new \yii\db\Expression(CmsTask::tableName().".id"),
        ]);

        $tasks = CmsTask::find()->select([
            CmsTask::tableName().'.*',
            'scheduleTotalTime' => $scheduleTotalTime,
            'planTotalTime'     => new \yii\db\Expression(CmsTask::tableName().".plan_duration"),
        ])->where([
            'executor_id' => $user->id,
        ])->andWhere([
            'status' => [
                CmsTask::STATUS_NEW,
                CmsTask::STATUS_IN_WORK,
                CmsTask::STATUS_ON_PAUSE,
                CmsTask::STATUS_ACCEPTED,
            ],
        ])->orderBy([
            'executor_sort' => SORT_ASC,
            'id'            => SORT_DESC,
        ])
            ->all();


        if ($sortTaskIds) {
            $tasksTmp = [];
            $tasks = ArrayHelper::map($tasks, 'id', function ($model) {
                return $model;
            });

            foreach ($sortTaskIds as $id) {
                if (isset($tasks[$id])) {
                    $tasksTmp[$id] = $tasks[$id];
                    unset($tasks[$id]);
                }
            }

            $resultTasks = ArrayHelper::merge((array)$tasksTmp, (array)$tasks);
            $tasks = $resultTasks;
            /*print_r(array_keys($tasks));die;*/
        }


        $priority = 0;
        $tasks = array_values((array)$tasks);
        $taskIndex = 0;
        $taskRemaining = null;

        for ($i = 0; $i <= 1000; $i++) {

            $workShedule = $user->work_shedule;
            $date = date("Y-m-d", strtotime("+{$i} day"));
            $times = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate($workShedule, $date);

            if (!$times) {
                continue;
            }

            $todayDate = \Yii::$app->formatter->asDate(time(), "php:Y-m-d");
            //Тут отфильтровать время согласно текущему времени
            if ($todayDate == $date) {
                $now = time();
                foreach ($times as $key => $period) {
                    if ($period->end_at <= $now) {
                        ArrayHelper::remove($times, $key);
                        continue;
                    }

                    if ($period->start_at < $now) {
                        $period->start_at = $now;
                    }
                }
            }

            if (!$times) {
                continue;
            }

            if (!isset($tasks[$taskIndex])) {
                break;
            }

            foreach ($times as $period)
            {
                $periodCursor = (int)$period->start_at;
                $periodEndAt = (int)$period->end_at;

                while (isset($tasks[$taskIndex]) && $periodCursor < $periodEndAt) {
                    $task = $tasks[$taskIndex];

                    if ($taskRemaining === null) {
                        //время по плану на задачу минус то что уже отработано по задаче
                        $taskRemaining = (int)$task->raw_row['planTotalTime'] - (int)$task->raw_row['scheduleTotalTime'];
                        if ($taskRemaining < 0) {
                            $taskRemaining = 0;
                        }
                    }

                    if ($taskRemaining <= 0) {
                        $priority++;
                        $taskEndAt = $periodCursor;
                        static::updateAll([
                            'executor_sort'   => $priority,
                            'executor_end_at' => $taskEndAt,
                            'plan_end_at'     => $taskEndAt,
                        ], ['id' => $task->id]);
                        $taskIndex++;
                        $taskRemaining = null;
                        continue;
                    }

                    $available = $periodEndAt - $periodCursor;
                    $duration = min($available, $taskRemaining);

                    $periodCursor += $duration;
                    $taskRemaining -= $duration;

                    if ($taskRemaining <= 0) {
                        $priority++;
                        static::updateAll([
                            'executor_sort'   => $priority,
                            'executor_end_at' => $periodCursor,
                            'plan_end_at'     => $periodCursor,
                        ], ['id' => $task->id]);
                        $taskIndex++;
                        $taskRemaining = null;
                    }
                }
            }
        }

        //Если приоритет задач обновил другой пользователь, то уведомим исполнителя
        if ($currentUser && $currentUser->id != $user->id) {
            $notify = new CmsWebNotify();
            $notify->cms_user_id = $user->id;
            $notify->name = $currentUser->asText . " поменял(а) порядок ваших задач.";
            $notify->model_id = $user->id;
            $notify->model_code = $user->skeeksModelCode;
            $notify->save();
        }
        return true;
    }

    /**
     * @return CmsTaskQuery
     */
    public static function find()
    {
        return new CmsTaskQuery(get_called_class());
    }

}
