<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\admin\CmsTaskGanttWidget */

use yii\helpers\Html;
?>
<?= Html::beginTag('div', $widget->options); ?>
<?= \skeeks\cms\widgets\admin\CmsTaskViewToolbarWidget::widget([
    'context' => $widget->context,
    'start'   => '<button type="button" class="btn btn-default btn-sm" data-gantt-shift="-1" title="Назад">'.\skeeks\cms\backend\helpers\BackendIcon::render('chevron-left', ['size' => 16]).'</button>'
        .'<button type="button" class="btn btn-default btn-sm" data-gantt-today>Сегодня</button>'
        .'<button type="button" class="btn btn-default btn-sm" data-gantt-shift="1" title="Вперёд">'.\skeeks\cms\backend\helpers\BackendIcon::render('chevron-right', ['size' => 16]).'</button>'
        .'<h2 class="sx-task-calendar__title" data-gantt-title></h2>',
    'tools'   => '<select class="form-control form-control-sm sx-task-gantt__group" data-gantt-group title="Группировка">'
        .'<option value="">Без групп</option><option value="project">По проекту</option><option value="client">По клиенту</option></select>'
        .'<div class="btn-group" role="group">'
        .'<button type="button" class="btn btn-sm btn-default" data-gantt-scale="day">День</button>'
        .'<button type="button" class="btn btn-sm btn-default" data-gantt-scale="week">Неделя</button>'
        .'<button type="button" class="btn btn-sm btn-default" data-gantt-scale="month">Месяц</button>'
        .'</div>',
]); ?>
<div class="sx-task-gantt__legend">
    <span><i class="sx-task-gantt__swatch is-fixed"></i> закреплено на время</span>
    <span><i class="sx-task-gantt__swatch is-auto"></i> по графику</span>
    <span><i class="sx-task-gantt__swatch is-in-work"></i> в работе</span>
    <span><i class="sx-task-gantt__swatch is-late"></i> просрочено</span>
    <span class="sx-task-gantt__legend-hint">Перетащите полосу — новое начало, правый край — длительность.</span>
</div>
<div class="sx-task-gantt__body" data-gantt-body></div>
<?= Html::endTag('div'); ?>
