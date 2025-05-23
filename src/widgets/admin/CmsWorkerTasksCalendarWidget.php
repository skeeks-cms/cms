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
     * @var array
     */
    public $options = [];

    public function run()
    {
        $this->options['id'] = $this->id;
        Html::addCssClass($this->options, "sx-worker-tasks-calendar-widget");

        if (\Yii::$app->request->isPost && \Yii::$app->request->isAjax) {
            if (\Yii::$app->request->post("widget") == $this->id) {

                ob_get_clean();
                ob_clean();

                $rr = new RequestResponse();

                CmsTask::recalculateTasksPriority($this->user, \Yii::$app->request->post("ids"));
                /*$this->user->recalculateTasksPriority();*/

                $rr->success = false;
                $rr->message = "Сохранено";

                \Yii::$app->response->data = $rr;
                \Yii::$app->end();
            }
        }

        $result = $this->render('worker-tasks-calendar');
        return $result;
    }
}