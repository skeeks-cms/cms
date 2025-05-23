<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\crm\widgets;

use common\models\User;
use skeeks\crm\models\CrmSchedule;
use skeeks\crm\models\CrmTask;
use skeeks\crm\models\CrmTaskSchedule;
use yii\base\Exception;
use yii\base\Widget;

/**
 * Виджет кнопок по задаче
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class TaskBtnsWidget extends Widget
{
    /**
     * @var CrmTask
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
         * @var $task CrmTask
         */
        $task = $this->task;

        $crmTaskSchedule = null;

        //Если задача в работе значит есть не остановленный промежуток времени
        if ($task->status == CrmTask::STATUS_IN_WORK) {
            $crmTaskSchedule = $task->notEndCrmTaskSchedule;
        }


        if (\Yii::$app->request->post() && \Yii::$app->request->post($this->id)) {

            $t = \Yii::$app->db->beginTransaction();
            //die;
            try {

//                $task = clone $this->task;
                $task = CrmTask::findOne($task->id);

                //var_dump($task);die;

                if ($task->load(\Yii::$app->request->post()) && $task->validate()) {
                    $oldAttributeStatus = $task->getOldAttribute('status');

                    if ($task->save()) {

                        if ($task->status == CrmTask::STATUS_IN_WORK) {
                            $crmTaskSchedule = new CrmTaskSchedule();
                            $crmTaskSchedule->cms_user_id = \Yii::$app->user->id;
                            $crmTaskSchedule->crm_task_id = $task->id;
                            $crmTaskSchedule->date = \Yii::$app->formatter->asDate(time(), "php:YY-m-d");
                            $crmTaskSchedule->start_time = \Yii::$app->formatter->asTime(time());
                            $crmTaskSchedule->end_time = null;

                            if (!$crmTaskSchedule->save()) {
                                $task->addError('end_time', 'Не удалось сохранить лог начала работы: ' . print_r($crmTaskSchedule->errors, true));
                                throw new Exception("Не удалось сохранить лог начала работы");
                            }
                        }

                        if (in_array($task->status, [CrmTask::STATUS_ON_PAUSE, CrmTask::STATUS_ON_CHECK]) && $oldAttributeStatus == CrmTask::STATUS_IN_WORK) {

                            $crmTaskSchedule->load(\Yii::$app->request->post());

                            if (!$crmTaskSchedule) {
                                $task->addError('end_time', 'Нет начального промежутка времени. Обратитесь к программисту!');
                                throw new Exception("Нет начального промежутка времени. Обратитесь к программисту!");
                            }


                            if (!$crmTaskSchedule->end_time) {
                                $crmTaskSchedule->end_time = \Yii::$app->formatter->asTime(time());
                            }



                            if (!$crmTaskSchedule->save()) {
                                $task->addError('end_time', 'Не удалось сохранить лог завершения работы: ' . print_r($crmTaskSchedule->errors, true));
                                throw new Exception("Не удалось сохранить лог завершения работы");
                            }
                        } elseif (in_array($task->status, [CrmTask::STATUS_ON_PAUSE]) && $oldAttributeStatus == CrmTask::STATUS_ON_CHECK) {

                        }

                        $this->task = $task;
                    } else {
                        throw new Exception('Не сохранился статус задачи');
                    }
                } else {
                    throw new Exception('Не сохранился статус задачи');
                }

                $t->commit();

            } catch (\Exception $e) {
                $t->rollBack();
                $error = $e->getMessage();

                $task->addError('end_time', $e->getMessage());
                //throw $e;
            }
        }

        return $this->render('task-btns', [
            'error' => $error,
            'task' => $task,
            'crmTaskSchedule' => $crmTaskSchedule,
        ]);
    }
}