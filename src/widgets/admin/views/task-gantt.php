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
<div class="sx-task-gantt__bar">
    <div class="sx-task-gantt__nav">
        <button type="button" class="btn btn-default btn-sm" data-gantt-shift="-1" title="Назад"><i class="fas fa-chevron-left"></i></button>
        <button type="button" class="btn btn-default btn-sm" data-gantt-today>Сегодня</button>
        <button type="button" class="btn btn-default btn-sm" data-gantt-shift="1" title="Вперёд"><i class="fas fa-chevron-right"></i></button>
        <span class="sx-task-gantt__title" data-gantt-title></span>
    </div>
    <div class="sx-task-gantt__tools">
        <? if (count($widget->users) > 1) : ?>
            <select class="form-control form-control-sm sx-task-gantt__user" data-gantt-user>
                <? foreach ($widget->users as $user) : ?>
                    <option value="<?= (int)$user->id; ?>" <?= (int)$user->id === (int)$widget->user->id ? 'selected' : ''; ?>><?= Html::encode($user->shortDisplayName); ?></option>
                <? endforeach; ?>
            </select>
        <? endif; ?>
        <select class="form-control form-control-sm sx-task-gantt__group" data-gantt-group title="Группировка">
            <option value="">Без групп</option>
            <option value="project">По проекту</option>
            <option value="client">По клиенту</option>
        </select>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-default" data-gantt-scale="day">День</button>
            <button type="button" class="btn btn-sm btn-default" data-gantt-scale="week">Неделя</button>
            <button type="button" class="btn btn-sm btn-default" data-gantt-scale="month">Месяц</button>
        </div>
    </div>
</div>
<div class="sx-task-gantt__legend">
    <span><i class="sx-task-gantt__swatch is-fixed"></i> закреплено на время</span>
    <span><i class="sx-task-gantt__swatch is-auto"></i> по графику</span>
    <span><i class="sx-task-gantt__swatch is-in-work"></i> в работе</span>
    <span><i class="sx-task-gantt__swatch is-late"></i> просрочено</span>
    <span class="sx-task-gantt__legend-hint">Перетащите полосу — новое начало, правый край — длительность.</span>
</div>
<div class="sx-task-gantt__body" data-gantt-body></div>
<?= Html::endTag('div'); ?>
