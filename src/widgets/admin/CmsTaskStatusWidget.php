<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\assets\BackendAsset;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsTask;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

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
     * Include schedule details in the tooltip. Collection renderers can
     * disable this to avoid loading schedules for every visible task.
     *
     * @var bool
     */
    public $showScheduleDetails = true;

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

        BackendAsset::register($this->view);

        $title = '';
        if ($this->showScheduleDetails && $schedules = $this->task->schedules) {
            $total = CmsScheduleHelper::durationAsTextBySchedules($schedules);

            $title .= "<br />Отработано: {$total}<br /><br />" . CmsScheduleHelper::getAsTextBySchedules($schedules);

        }

        $variant = ArrayHelper::getValue([
            CmsTask::STATUS_ACCEPTED => 'accent',
            CmsTask::STATUS_CANCELED => 'danger',
            CmsTask::STATUS_IN_WORK => 'success',
            CmsTask::STATUS_ON_PAUSE => 'warning',
            CmsTask::STATUS_ON_CHECK => 'info',
            CmsTask::STATUS_READY => 'success',
        ], $this->task->status, 'neutral');

        $options = ArrayHelper::merge((array)$this->options, [
            'title' => $this->task->statusAsHint . $title,
            'data-toggle' => 'tooltip',
            'data-html' => 'true',
            'style' => 'cursor: unset;',
        ]);
        Html::addCssClass($options, [
            'btn',
            'btn-xs',
            'label-status-task',
            'label-status-'.$this->task->status,
            'sx-status',
            'sx-status--'.$variant,
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
