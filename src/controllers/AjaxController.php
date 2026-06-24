<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

/* @var $this yii\web\View */

namespace skeeks\cms\controllers;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\models\CmsCountry;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\models\CmsTreeProperty;
use skeeks\cms\models\CmsTreeTypeProperty;
use skeeks\cms\models\CmsTreeTypePropertyEnum;
use skeeks\cms\models\CmsUserSchedule;
use skeeks\cms\models\CmsUserUniversalProperty;
use skeeks\cms\models\CmsUserUniversalPropertyEnum;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\shop\models\ShopBrand;
use skeeks\cms\shop\models\ShopCollection;
use yii\web\Controller;
use yii\web\Response;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AjaxController extends Controller
{
    const IDLE_WORK_REMINDER_DELAY = 3600;

    protected function getDayStart($timestamp)
    {
        return strtotime(date('Y-m-d 00:00:00', (int) $timestamp));
    }

    protected function getDayEnd($timestamp)
    {
        return strtotime(date('Y-m-d 23:59:59', (int) $timestamp));
    }

    protected function formatDuration($seconds)
    {
        return \skeeks\cms\helpers\CmsScheduleHelper::durationAsText((int) max(0, $seconds));
    }

    protected function getStaleWorkReminderData()
    {
        if (\Yii::$app->user->isGuest) {
            return null;
        }

        $user = \Yii::$app->user->identity;
        $todayStart = $this->getDayStart(time());
        $cmsUserSchedule = CmsUserSchedule::find()
            ->user($user)
            ->notEnd()
            ->andWhere(['<', 'start_at', $todayStart])
            ->orderBy(['start_at' => SORT_ASC])
            ->one();

        if (!$cmsUserSchedule) {
            return null;
        }

        $dayStart = $this->getDayStart((int) $cmsUserSchedule->start_at);
        $dayEnd = $this->getDayEnd((int) $cmsUserSchedule->start_at);
        $maxEndAt = min($dayEnd, time());
        $minEndAt = (int) $cmsUserSchedule->start_at + 60;
        $workIntervals = [];

        $userSchedules = CmsUserSchedule::find()
            ->user($user)
            ->andWhere(['<', 'start_at', $dayEnd])
            ->andWhere([
                'or',
                ['end_at' => null],
                ['>', 'end_at', $dayStart],
            ])
            ->orderBy(['start_at' => SORT_ASC])
            ->all();

        foreach ($userSchedules as $schedule) {
            $intervalStart = max((int) $schedule->start_at, $dayStart);
            $intervalEnd = $schedule->end_at ? min((int) $schedule->end_at, $dayEnd) : null;
            $workIntervals[] = [
                'id' => (int) $schedule->id,
                'is_current' => (int) $schedule->id === (int) $cmsUserSchedule->id,
                'start_at' => $intervalStart,
                'end_at' => $intervalEnd,
                'start_time' => \Yii::$app->formatter->asTime($intervalStart, 'short'),
                'end_time' => $intervalEnd ? \Yii::$app->formatter->asTime($intervalEnd, 'short') : null,
                'duration' => $intervalEnd ? $this->formatDuration($intervalEnd - $intervalStart) : null,
            ];
        }

        $taskIntervals = [];
        $taskSchedules = CmsTaskSchedule::find()
            ->andWhere(['cms_user_id' => $user->id])
            ->andWhere(['<', 'start_at', $dayEnd])
            ->andWhere([
                'or',
                ['end_at' => null],
                ['>', 'end_at', (int) $cmsUserSchedule->start_at],
            ])
            ->orderBy(['start_at' => SORT_ASC])
            ->all();

        foreach ($taskSchedules as $taskSchedule) {
            $taskStart = max((int) $taskSchedule->start_at, $dayStart);
            $taskEnd = $taskSchedule->end_at ? min((int) $taskSchedule->end_at, $dayEnd) : null;
            if ($taskSchedule->end_at) {
                $minEndAt = max($minEndAt, (int) $taskSchedule->end_at);
            } else {
                $minEndAt = max($minEndAt, (int) $taskSchedule->start_at + 60);
            }

            $taskIntervals[] = [
                'id' => (int) $taskSchedule->id,
                'task_id' => (int) $taskSchedule->cms_task_id,
                'task_name' => $taskSchedule->cmsTask ? $taskSchedule->cmsTask->name : ('#' . $taskSchedule->cms_task_id),
                'is_open' => !$taskSchedule->end_at,
                'start_at' => $taskStart,
                'end_at' => $taskEnd,
                'start_time' => \Yii::$app->formatter->asTime($taskStart, 'short'),
                'end_time' => $taskEnd ? \Yii::$app->formatter->asTime($taskEnd, 'short') : null,
                'duration' => $taskEnd ? $this->formatDuration($taskEnd - $taskStart) : null,
            ];
        }

        return [
            'schedule_id' => (int) $cmsUserSchedule->id,
            'date_key' => date('Y-m-d', (int) $cmsUserSchedule->start_at),
            'work_date' => \Yii::$app->formatter->asDate((int) $cmsUserSchedule->start_at, 'long'),
            'is_yesterday' => $dayStart === strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))),
            'start_at' => (int) $cmsUserSchedule->start_at,
            'start_time' => \Yii::$app->formatter->asTime((int) $cmsUserSchedule->start_at, 'short'),
            'start_datetime' => \Yii::$app->formatter->asDatetime((int) $cmsUserSchedule->start_at, 'short'),
            'min_end_at' => $minEndAt,
            'max_end_at' => $maxEndAt,
            'min_end_time' => date('H:i', $minEndAt),
            'max_end_time' => date('H:i', $maxEndAt),
            'work_intervals' => $workIntervals,
            'task_intervals' => $taskIntervals,
        ];
    }

    protected function getIdleWorkReminderData()
    {
        if (\Yii::$app->user->isGuest) {
            return null;
        }

        $user = \Yii::$app->user->identity;
        $cmsUserSchedule = CmsUserSchedule::find()->user($user)->notEnd()->one();
        if (!$cmsUserSchedule) {
            return null;
        }

        $currentTaskSchedule = CmsTaskSchedule::find()
            ->andWhere(['cms_user_id' => $user->id])
            ->notEnd()
            ->one();
        if ($currentTaskSchedule) {
            return null;
        }

        if (CmsTask::find()->executor($user)->statusInWork()->one()) {
            return null;
        }

        $lastTaskSchedule = CmsTaskSchedule::find()
            ->andWhere(['cms_user_id' => $user->id])
            ->andWhere(['not', ['end_at' => null]])
            ->andWhere(['>=', 'end_at', $cmsUserSchedule->start_at])
            ->orderBy(['end_at' => SORT_DESC])
            ->one();

        if (!$lastTaskSchedule) {
            $idleSeconds = time() - (int) $cmsUserSchedule->start_at;
            if ($idleSeconds < static::IDLE_WORK_REMINDER_DELAY) {
                return null;
            }

            return [
                'schedule_id'      => (int) $cmsUserSchedule->id,
                'last_task_end_at' => (int) $cmsUserSchedule->start_at,
                'last_task_time'   => \Yii::$app->formatter->asTime((int) $cmsUserSchedule->start_at, 'short'),
                'last_task_date'   => \Yii::$app->formatter->asDatetime((int) $cmsUserSchedule->start_at, 'short'),
                'idle_seconds'     => $idleSeconds,
                'idle_duration'    => \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($idleSeconds),
                'reason'           => 'no_tasks',
            ];
        }

        $idleSeconds = time() - (int) $lastTaskSchedule->end_at;
        if ($idleSeconds < static::IDLE_WORK_REMINDER_DELAY) {
            return null;
        }

        return [
            'schedule_id'      => (int) $cmsUserSchedule->id,
            'last_task_end_at' => (int) $lastTaskSchedule->end_at,
            'last_task_time'   => \Yii::$app->formatter->asTime((int) $lastTaskSchedule->end_at, 'short'),
            'last_task_date'   => \Yii::$app->formatter->asDatetime((int) $lastTaskSchedule->end_at, 'short'),
            'idle_seconds'     => $idleSeconds,
            'idle_duration'    => \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($idleSeconds),
            'reason'           => 'last_task_finished',
        ];
    }

    public function actionIdleWorkCheck()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'success' => true,
            'data'    => $this->getIdleWorkReminderData(),
        ];
    }

    public function actionIdleWorkStop()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $idleWorkReminderData = $this->getIdleWorkReminderData();
            if (!$idleWorkReminderData) {
                throw new \Exception("Нет рабочего периода для завершения.");
            }

            $cmsUserSchedule = CmsUserSchedule::find()
                ->user(\Yii::$app->user->identity)
                ->notEnd()
                ->andWhere(['id' => $idleWorkReminderData['schedule_id']])
                ->one();

            if (!$cmsUserSchedule) {
                throw new \Exception("Нет рабочего периода для завершения.");
            }

            $cmsUserSchedule->end_at = $idleWorkReminderData['last_task_end_at'];
            if (!$cmsUserSchedule->save(false)) {
                throw new \Exception(implode("\n", $cmsUserSchedule->getFirstErrors()));
            }

            return [
                'success' => true,
                'data'    => $idleWorkReminderData,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function actionStaleWorkCheck()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'success' => true,
            'data' => $this->getStaleWorkReminderData(),
        ];
    }

    public function actionStaleWorkStop()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = $this->getStaleWorkReminderData();
            if (!$data) {
                throw new \Exception("Нет незавершенного рабочего дня для исправления.");
            }

            $endTime = trim((string) \Yii::$app->request->post('end_time'));
            if (!preg_match('/^\d{1,2}:\d{2}$/', $endTime)) {
                throw new \Exception("Укажите время завершения в формате ЧЧ:ММ.");
            }

            list($hours, $minutes) = array_map('intval', explode(':', $endTime));
            if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
                throw new \Exception("Укажите корректное время завершения.");
            }

            $endAt = strtotime($data['date_key'] . ' ' . sprintf('%02d:%02d:00', $hours, $minutes));
            if (!$endAt) {
                throw new \Exception("Не удалось распознать время завершения.");
            }

            if ($endAt <= (int) $data['start_at']) {
                throw new \Exception("Время завершения должно быть больше времени начала рабочего промежутка.");
            }

            if ($endAt > (int) $data['max_end_at']) {
                throw new \Exception("Время завершения не может быть позже этого дня.");
            }

            $cmsUserSchedule = CmsUserSchedule::find()
                ->user(\Yii::$app->user->identity)
                ->notEnd()
                ->andWhere(['id' => $data['schedule_id']])
                ->one();

            if (!$cmsUserSchedule) {
                throw new \Exception("Рабочий промежуток уже завершен.");
            }

            $closedTaskAfterEnd = CmsTaskSchedule::find()
                ->andWhere(['cms_user_id' => \Yii::$app->user->id])
                ->andWhere(['not', ['end_at' => null]])
                ->andWhere(['>=', 'start_at', (int) $cmsUserSchedule->start_at])
                ->andWhere(['>', 'end_at', $endAt])
                ->orderBy(['end_at' => SORT_DESC])
                ->one();

            if ($closedTaskAfterEnd) {
                throw new \Exception("Время завершения не может быть меньше завершенной работы по задаче: " . \Yii::$app->formatter->asTime((int) $closedTaskAfterEnd->end_at, 'short') . ".");
            }

            $openTaskAfterEnd = CmsTaskSchedule::find()
                ->andWhere(['cms_user_id' => \Yii::$app->user->id])
                ->notEnd()
                ->andWhere(['>=', 'start_at', (int) $cmsUserSchedule->start_at])
                ->andWhere(['>=', 'start_at', $endAt])
                ->orderBy(['start_at' => SORT_DESC])
                ->one();

            if ($openTaskAfterEnd) {
                throw new \Exception("Время завершения не может быть меньше времени начала незавершенной задачи: " . \Yii::$app->formatter->asTime((int) $openTaskAfterEnd->start_at, 'short') . ".");
            }

            $openTaskSchedules = CmsTaskSchedule::find()
                ->andWhere(['cms_user_id' => \Yii::$app->user->id])
                ->notEnd()
                ->andWhere(['>=', 'start_at', (int) $cmsUserSchedule->start_at])
                ->andWhere(['<', 'start_at', $endAt])
                ->all();

            foreach ($openTaskSchedules as $taskSchedule) {
                $taskSchedule->end_at = $endAt;
                if (!$taskSchedule->save(false)) {
                    throw new \Exception(implode("\n", $taskSchedule->getFirstErrors()));
                }

                if ($taskSchedule->cmsTask && $taskSchedule->cmsTask->status == CmsTask::STATUS_IN_WORK) {
                    $taskSchedule->cmsTask->status = CmsTask::STATUS_ON_PAUSE;
                    if (!$taskSchedule->cmsTask->save(false, ['status'])) {
                        throw new \Exception(implode("\n", $taskSchedule->cmsTask->getFirstErrors()));
                    }
                }
            }

            $cmsUserSchedule->end_at = $endAt;
            if (!$cmsUserSchedule->save(false)) {
                throw new \Exception(implode("\n", $cmsUserSchedule->getFirstErrors()));
            }

            $transaction->commit();

            return [
                'success' => true,
                'data' => [
                    'end_at' => $endAt,
                    'end_time' => \Yii::$app->formatter->asDatetime($endAt, 'short'),
                ],
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array
     */
    public function actionAutocompleteEavOptions()
    {
        $result = [];
        $property_id = (int) \Yii::$app->request->get("property_id");
        if (!$property_id) {
            return $result;
        }

        $propertyClass = CmsContentProperty::class;
        $propertyEnumClass = CmsContentPropertyEnum::class;
        
        if (\Yii::$app->request->get("property_class")) {
            $propertyClass = (string) \Yii::$app->request->get("property_class");
        }
        
        if (\Yii::$app->request->get("property_enum_class")) {
            $propertyEnumClass = (string) \Yii::$app->request->get("property_enum_class");
        }
        
        $q = $propertyClass::find()->cmsSite()->andWhere(['id' => $property_id]);

        /**
         * @var $property CmsContentProperty
         */
        if (!$property = $q->one()) {
            return $result;
        }
        

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = $propertyEnumClass::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(50)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->value,
                    ];
                }
            }
        } elseif ($property->property_type == PropertyType::CODE_ELEMENT) {
            if (!isset($property->handler->content_id) || ! $property->handler->content_id) {
                return $result;
            }

            $query = CmsContentElement::find()->cmsSite()->active()->andWhere(['content_id' => $property->handler->content_id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'name', $q]);
            }

            $data = $query->limit(50)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->name,
                    ];
                }
            }
        }


        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return ['results' => $result];
    }
    /**
     * @return array
     */
    public function actionAutocompleteUserEavOptions()
    {
        $result = [];

        $property_id = (int) \Yii::$app->request->get("property_id");
        if (!$property_id) {
            return $result;
        }


        $propertyClass = CmsUserUniversalProperty::class;
        $propertyEnumClass = CmsUserUniversalPropertyEnum::class;

        if (\Yii::$app->request->get("property_class")) {
            $propertyClass = (string) \Yii::$app->request->get("property_class");
        }

        if (\Yii::$app->request->get("property_enum_class")) {
            $propertyEnumClass = (string) \Yii::$app->request->get("property_enum_class");
        }

        /**
         * @var $property CmsContentProperty
         */
        if (!$property = $propertyClass::find()->cmsSite()->andWhere(['id' => $property_id])->one()) {
            return $result;
        }

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = $propertyEnumClass::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(50)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->value,
                    ];
                }
            }
        } elseif ($property->property_type == PropertyType::CODE_ELEMENT) {
            if (!isset($property->handler->content_id) || ! $property->handler->content_id) {
                return $result;
            }

            $query = CmsContentElement::find()->cmsSite()->active()->andWhere(['content_id' => $property->handler->content_id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'name', $q]);
            }

            $data = $query->limit(50)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->name,
                    ];
                }
            }
        }


        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return ['results' => $result];
    }

    /**
     * @return array
     */
    public function actionAutocompleteTreeEavOptions()
    {
        $result = [];

        $property_id = (int) \Yii::$app->request->get("property_id");
        if (!$property_id) {
            return $result;
        }


        /**
         * @var $property CmsContentProperty
         */
        if (!$property = CmsTreeTypeProperty::find()->where(['property_id' => $property_id])->one()) {
            return $result;
        }

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = CmsTreeTypePropertyEnum::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(50)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->value,
                    ];
                }
            }
        } elseif ($property->property_type == PropertyType::CODE_ELEMENT) {
            if (!isset($property->handler->content_id) || ! $property->handler->content_id) {
                return $result;
            }

            $query = CmsContentElement::find()->active()->andWhere(['content_id' => $property->handler->content_id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'name', $q]);
            }

            $data = $query->limit(50)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->name,
                    ];
                }
            }
        }


        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return ['results' => $result];
    }


    /**
     * @return void
     */
    public function actionAdult()
    {
        $rr = new RequestResponse();

        if (\Yii::$app->request->post("is_allow")) {
            \Yii::$app->adult->isAllowAdult = true;
            $rr->success = true;
        }

        return $rr;
    }

    /**
     * @return array
     */
    public function actionAutocompleteCountries()
    {
        $result = [];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;


        $query = CmsCountry::find();

        if ($q = \Yii::$app->request->get('q')) {
            $query->search($q);
        }

        $data = $query->limit(100)
            ->all();

        if ($data) {

            /**
             * @var $model CmsCountry
             */
            foreach ($data as $model) {
                $result[] = [
                    'id'   => $model->alpha2,
                    'text' => $model->name,
                ];
            }
        }

        return ['results' => $result];
    }
    
    /**
     * @return array
     */
    public function actionAutocompleteCollections()
    {
        $result = [];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $query = ShopCollection::find();

        if ($q = \Yii::$app->request->get('q')) {
            $query->search($q);
        }
        if ($brand_id = \Yii::$app->request->get('brand_id')) {
            $query->andWhere(['shop_brand_id' => $brand_id]);
        }

        $data = $query->limit(100)
            ->all();

        if ($data) {

            /**
             * @var $model CmsContentElement
             */
            foreach ($data as $model) {
                $result[] = [
                    'id'   => $model->id,
                    'text' => $model->name,
                ];
            }
        }

        return ['results' => $result];
    }
    
    public function actionWebNotifiesNew() {
        $rr = new RequestResponse();
        $rr->success = true;

        if (\Yii::$app->user->isGuest) {
            $rr->data = [
                'total' => 0,
                'items' => [],
            ];

            return $rr;
        }

        $qNotifies = \Yii::$app->user->identity->getCmsWebNotifies()->notRead()->limit(3);

        if ($last_notify_id = (int) \Yii::$app->request->post("last_notify_id")) {
            $qNotifies->andWhere(['>', 'id', $last_notify_id]);
        }

        $qNotifiesNotPopups = $qNotifies->all();
        $qNotifiesNotPopupsArray = [];

        if ($qNotifiesNotPopups) {
            /**
             * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
             */
            foreach ($qNotifiesNotPopups as $qNotifiesNotPopup)
            {
                $qNotifiesNotPopupsArray[] = \yii\helpers\ArrayHelper::merge(['render' => $qNotifiesNotPopup->getHtml()], $qNotifiesNotPopup->toArray());
            }
        }

        $rr->data = [
            'total' => \Yii::$app->user->identity->getCmsWebNotifies()->notRead()->count(),
            'items' => $qNotifiesNotPopupsArray,
        ];

        return $rr;
    }
    public function actionWebNotifiesClear() {
        $rr = new RequestResponse();
        $rr->success = true;

        if (\Yii::$app->user->isGuest) {

            return $rr;
        }

        $qNotifies = \Yii::$app->user->identity->getCmsWebNotifies();

        if ($qNotifies->count()) {
            /**
             * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
             */
            foreach ($qNotifies->each(10) as $qNotifiesNotPopup)
            {
                $qNotifiesNotPopup->delete();
            }
        }

        return $rr;
    }

    public function actionWebNotifies()
    {
        $rr = new RequestResponse();
        $rr->success = true;

        if (\Yii::$app->user->isGuest) {
            $rr->data = [
                'total' => 0,
                'items' => [],
            ];

            return $rr;
        }


        $qNotifiesAll = \Yii::$app->user->identity->getCmsWebNotifies()->orderBy(['is_read' => SORT_ASC, 'created_at' => SORT_DESC])->limit(40);

        $qNotifiesNotPopupsArray = [];
        $qNotifiesNotPopups = $qNotifiesAll->all();

        if ($qNotifiesNotPopups) {
            /**
             * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
             */
            foreach ($qNotifiesNotPopups as $qNotifiesNotPopup)
            {
                $qNotifiesNotPopupsArray[] = \yii\helpers\ArrayHelper::merge(['render' => $qNotifiesNotPopup->getHtml()], $qNotifiesNotPopup->toArray());

                $qNotifiesNotPopup->is_read = 1;
                $qNotifiesNotPopup->update(false, ['is_read']);
            }
        }

        $qNotifies = \Yii::$app->user->identity->getCmsWebNotifies()->notRead();

        $rr->data = [
            'total' => $qNotifies->count(),
            'items' => $qNotifiesNotPopupsArray,
        ];

        return $rr;
    }

    /**
     * @return array
     */
    public function actionAutocompleteBrands()
    {
        $result = [];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $query = ShopBrand::find();

        if ($q = \Yii::$app->request->get('q')) {
            $query->search($q);
        }

        $data = $query->limit(100)
            ->all();

        if ($data) {

            /**
             * @var $model CmsContentElement
             */
            foreach ($data as $model) {
                $result[] = [
                    'id'   => $model->id,
                    'text' => $model->name,
                ];
            }
        }

        return ['results' => $result];
    }

}
