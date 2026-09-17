<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\models\CmsUserSchedule;
use yii\base\Exception;
use yii\base\Widget;

/**
 * Виджет кнопок по задаче
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsTaskBtnsWidget extends Widget
{
    /**
     * @var CmsTask
     */
    public $task = null;

    /**
     * @var bool
     */
    public $isPjax = true;

    public function getUser()
    {
        return \Yii::$app->user->identity;
    }

    public function run()
    {
        $error = '';

        /**
         * @var $task CmsTask
         */
        $task = $this->task;

        $CmsTaskSchedule = null;

        //Если задача в работе значит есть не остановленный промежуток времени
        if ($task->status == CmsTask::STATUS_IN_WORK) {
            $CmsTaskSchedule = CmsTaskSchedule::find()->task($task)->notEnd()->one();
            /*$CmsTaskSchedule = $task->notEndCmsTaskSchedule;*/
        }

        $isSaved = false;
        $isUserScheduleStarted = false;


        if (\Yii::$app->request->post() && \Yii::$app->request->post($this->id)) {

            try {
                $attributes = (array)\Yii::$app->request->post('CmsTask', []);
                $scheduleAttributes = (array)\Yii::$app->request->post('CmsTaskSchedule', []);
                $result = (new \skeeks\cms\services\TaskWorkflow())->transition(
                    $task,
                    (string)($attributes['status'] ?? ''),
                    !empty($scheduleAttributes['end_at']) ? (int)$scheduleAttributes['end_at'] : null
                );
                $this->task = $task;
                $CmsTaskSchedule = $result['schedule'];
                $isUserScheduleStarted = $result['work_time_started'];
                $isSaved = true;
            } catch (\Throwable $e) {
                $task->refresh();
                $error = $e->getMessage();
                $task->addError('status', $error);
            }
        }

        return $this->render('task-btns', [
            'error' => $error,
            'task' => $task,
            'isSaved' => $isSaved,
            'isUserScheduleStarted' => $isUserScheduleStarted,
            'CmsTaskSchedule' => $CmsTaskSchedule,
        ]);
    }
}
