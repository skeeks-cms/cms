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
use skeeks\crm\models\CrmSchedule;
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


        if (\Yii::$app->request->post() && \Yii::$app->request->post($this->id)) {

            $t = \Yii::$app->db->beginTransaction();
            //die;
            try {

//                $task = clone $this->task;
                $task = CmsTask::findOne($task->id);

                //var_dump($task);die;

                if ($task->load(\Yii::$app->request->post()) && $task->validate()) {
                    $oldAttributeStatus = $task->getOldAttribute('status');

                    if ($task->save()) {

                        if ($task->status == CmsTask::STATUS_IN_WORK) {
                            $CmsTaskSchedule = new CmsTaskSchedule();
                            $CmsTaskSchedule->cms_user_id = \Yii::$app->user->id;
                            $CmsTaskSchedule->cms_task_id = $task->id;
                            $CmsTaskSchedule->start_at = time();
                            $CmsTaskSchedule->end_at = null;

                            if (!$CmsTaskSchedule->save()) {
                                $task->addError('end_at', 'Не удалось сохранить лог начала работы: ' . print_r($CmsTaskSchedule->errors, true));
                                throw new Exception("Не удалось сохранить лог начала работы");
                            }
                        }

                        if (in_array($task->status, [CmsTask::STATUS_ON_PAUSE, CmsTask::STATUS_ON_CHECK, CmsTask::STATUS_READY]) && $oldAttributeStatus == CmsTask::STATUS_IN_WORK) {

                            $CmsTaskSchedule->load(\Yii::$app->request->post());

                            if (!$CmsTaskSchedule) {
                                $task->addError('end_at', 'Нет начального промежутка времени. Обратитесь к программисту!');
                                throw new Exception("Нет начального промежутка времени. Обратитесь к программисту!");
                            }


                            if (!$CmsTaskSchedule->end_at) {
                                $CmsTaskSchedule->end_at = time();
                            }

                            if (!$CmsTaskSchedule->save()) {
                                $task->addError('end_at', 'Не удалось сохранить лог завершения работы: ' . print_r($CmsTaskSchedule->errors, true));
                                throw new Exception("Не удалось сохранить лог завершения работы");
                            }
                        } elseif (in_array($task->status, [CmsTask::STATUS_ON_PAUSE]) && $oldAttributeStatus == CmsTask::STATUS_ON_CHECK) {

                        }

                        $this->task = $task;
                    } else {
                        throw new Exception('Не сохранился статус задачи');
                    }
                } else {
                    throw new Exception('Не сохранился статус задачи');
                }

                $t->commit();
                $isSaved = true;

            } catch (\Exception $e) {
                $t->rollBack();
                $error = $e->getMessage();

                $task->addError('end_at', $e->getMessage());
                //throw $e;
            }
        }

        return $this->render('task-btns', [
            'error' => $error,
            'task' => $task,
            'isSaved' => $isSaved,
            'CmsTaskSchedule' => $CmsTaskSchedule,
        ]);
    }
}