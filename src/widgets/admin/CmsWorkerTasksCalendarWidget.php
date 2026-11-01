<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsTask;
use skeeks\cms\backend\assets\WorkerTasksCalendarAsset;
use yii\base\Widget;
use yii\helpers\Html;

class CmsWorkerTasksCalendarWidget extends Widget
{
    /**
     * @var string
     */
    public static $autoIdPrefix = 'CmsWorkerTasksCalendarWidget';

    /**
     * @var User
     */
    public $user = null;

    /**
     * @var TaskViewContext|null whose tasks and the search; null — the employee $user, fixed (an employee's card)
     */
    public $context;

    /**
     * @var array
     */
    public $options = [];

    public function run()
    {
        if (!$this->context) {
            $this->context = TaskViewContext::forUser($this->user);
        }
        $this->user = $this->context->user;

        WorkerTasksCalendarAsset::register($this->view);
        // The common toolbar of task views lives in the task calendar asset.
        \skeeks\cms\assets\admin\TaskCalendarAsset::register($this->view);

        $this->options['id'] = $this->id;
        Html::addCssClass($this->options, "sx-worker-tasks-calendar-widget");

        if (\Yii::$app->request->isPost && \Yii::$app->request->isAjax) {
            if (\Yii::$app->request->post("widget") == $this->id) {

                ob_get_clean();
                ob_clean();

                $rr = new RequestResponse();
                $ids = array_values(array_unique(array_filter(array_map('intval', (array)\Yii::$app->request->post("ids")))));
                if ($error = $this->orderError($ids)) {
                    $rr->success = false;
                    $rr->message = $error;
                } else {
                    try {
                        CmsTask::recalculateTasksPriority($this->user, $ids);
                        $rr->success = true;
                        $rr->message = "Порядок задач сохранён";
                    } catch (\yii\base\Exception $e) {
                        $rr->success = false;
                        $rr->message = \skeeks\cms\services\TaskPlanner::BUSY_MESSAGE;
                    }
                }

                \Yii::$app->response->data = $rr;
                \Yii::$app->end();
            }
        }

        $result = $this->render('worker-tasks-calendar');
        return $result;
    }

    /**
     * Why the order cannot be saved: an empty list, an id that is not a task of this executor the person may
     * see (another executor's, a missing one), or a task that is not in the queue — fixed, in work or closed.
     * Nothing is laid out then.
     */
    protected function orderError(array $ids): ?string
    {
        if (!$ids) {
            return 'Не передан порядок задач.';
        }
        $user = \Yii::$app->user->identity;
        $t = CmsTask::tableName();
        $rows = CmsTask::find()->forManager($user)
            ->select(array_merge([$t.'.id', $t.'.status'], CmsTask::getTableSchema()->getColumn('is_fixed_start') ? [$t.'.is_fixed_start'] : []))
            ->andWhere([$t.'.id' => $ids, $t.'.executor_id' => (int)$this->user->id])
            ->asArray()->all();
        if (count($rows) !== count($ids)) {
            return 'Порядок не сохранён: в списке есть задачи другого сотрудника или недоступные вам. Обновите страницу.';
        }
        $queue = array_diff(CmsTask::activeStatuses(), [CmsTask::STATUS_IN_WORK]);
        foreach ($rows as $row) {
            if (!empty($row['is_fixed_start']) || !in_array($row['status'], $queue, true)) {
                return 'Порядок не сохранён: закреплённые, начатые и закрытые задачи не входят в очередь. Обновите страницу.';
            }
        }

        return null;
    }
}
