<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsTask;
use skeeks\cms\widgets\user\assets\UserOnlineWidgetAsset;
use skeeks\crm\helpers\CrmScheduleHelper;
use skeeks\crm\models\CrmSchedule;
use skeeks\crm\models\CrmTask;
use yii\base\Widget;
use yii\helpers\ArrayHelper;use yii\helpers\Html;

/**
 * Виджет отображения статуса задачи
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsTaskStatusWidget extends Widget
{
    /**
     * @var CmsTask
     */
    public $task = null;

    /**
     * @var bool
     */
    public $isShort = false;

    /**
     * @var null
     */
    public $options = null;

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        if (!$this->task) {
            return '';
        }

        $title = '';
        if ($schedules = $this->task->schedules) {
            $total = CmsScheduleHelper::durationAsTextBySchedules($schedules);

            $title .= "<br />Отработано: {$total}<br /><br />" . CmsScheduleHelper::getAsTextBySchedules($schedules);

        }
        
$accepted = \skeeks\cms\models\CmsTask::STATUS_ACCEPTED;
$canceled = \skeeks\cms\models\CmsTask::STATUS_CANCELED;
$work = \skeeks\cms\models\CmsTask::STATUS_IN_WORK;
$on_pause = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE;
$on_check = \skeeks\cms\models\CmsTask::STATUS_ON_CHECK;
$ready = \skeeks\cms\models\CmsTask::STATUS_READY;

        $this->view->registerCss(<<<CSS
.label-status-task {
    color: white;
    background: #bbb;
    border-radius: var(--border-radius);
}
.label-status-task:hover {
    color: white;
}
.label-status-{$accepted} {
    background: #9a69cb;
}

.label-status-{$canceled} {
    background: var(--color-red-pale);
}

.label-status-{$work} {
    background-color: #22e3be;
    transition: all .2s;
      animation: sx-label-pulse 1.5s infinite linear;
}
.label-status-{$on_pause} {
    background-color: #e57d20;
}
.label-status-{$on_check} {
    background-color: #00bed6;
}
.label-status-{$ready} {
    background-color: green;
}

@keyframes sx-label-pulse {
  0% {
    box-shadow: 0 0 5px 0px #22e3be, 0 0 5px 0px #22e3be; 
  }
  100% {
    box-shadow: 0 0 5px 6px rgba(255, 48, 26, 0), 0 0 4px 10px rgba(255, 48, 26, 0); 
  } 
}

CSS
);

        $options = ArrayHelper::merge($this->options, [
            'title' => $this->task->statusAsHint . $title,
            'data-toggle' => 'tooltip',
            'data-html' => 'true',
            'style' => 'cursor: unset;',
            'class' => 'btn btn-xs label-status-task label-status-' . $this->task->status,
        ]);


        $title = " " . $this->task->statusAsText;

        if ($this->isShort) {
            $title = "";
        }

        return \yii\helpers\Html::tag("span",
            Html::tag('i', '', [
                'class' => $this->task->statusAsIcon
]) . $title
        , $options);

    }
}