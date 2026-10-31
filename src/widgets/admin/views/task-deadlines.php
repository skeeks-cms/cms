<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\admin\CmsTaskDeadlinesWidget */
/* @var $columns array */

use yii\helpers\Html;

$f = \Yii::$app->formatter;
$moveUrl = \yii\helpers\Url::to(['/cms/admin-cms-task/reschedule']);
?>
<?= Html::beginTag('div', $widget->options); ?>
<p class="sx-task-deadlines__hint text-muted">
    Перетащите задачу в другую колонку — она встанет в первое свободное время этого дня по графику и закрепится.
</p>
<div class="sx-task-deadlines__board" data-sx-task-deadlines id="<?= $widget->id; ?>-board" data-move-url="<?= Html::encode($moveUrl); ?>">
    <? foreach ($columns as $key => $column) : ?>
        <div class="sx-task-deadlines__column <?= $key === 'late' ? 'is-late' : ''; ?>" data-sx-deadlines-column="<?= $key; ?>" data-day="<?= Html::encode((string)$column['day']); ?>">
            <div class="sx-task-deadlines__head">
                <span><?= Html::encode($column['title']); ?> <span class="sx-task-deadlines__sub"><?= Html::encode($column['sub']); ?></span></span>
                <span class="sx-task-deadlines__sub"><?= count($column['tasks']); ?></span>
            </div>
            <div class="sx-task-deadlines__list" data-sx-deadlines-list>
                <? foreach (array_slice($column['tasks'], 0, $widget->columnLimit) as $task) : ?>
                    <?
                    $isFixed = !$task->hasAttribute('is_fixed_start') || (bool)$task->is_fixed_start;
                    $url = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-cms-task/view', 'pk' => $task->id])
                        ->enableEmptyLayout()->enableNoActions()->url;
                    $client = $task->cmsProject ? $task->cmsProject->name : ($task->cmsCompany ? $task->cmsCompany->name : ($task->cmsUser ? $task->cmsUser->shortDisplayName : ''));
                    $start = (int)$task->plan_start_at;
                    $canMove = $widget->canMove($task);
                    ?>
                    <div class="sx-task-deadlines__card <?= $key === 'late' ? 'is-late' : ''; ?>" <?= $canMove ? 'data-sx-deadlines-card' : ''; ?> data-pk="<?= (int)$task->id; ?>">
                        <? if ($canMove) : ?>
                            <span class="sx-task-deadlines__handle" data-sx-deadlines-handle title="Перетащите в другую колонку"><i class="fas fa-grip-vertical"></i></span>
                        <? endif; ?>
                        <div class="sx-task-deadlines__card-time">
                            <span><?= in_array($key, ['today', 'tomorrow'], true) ? $f->asTime($start, 'php:H:i') : $f->asDate($start, 'php:j M').', '.$f->asTime($start, 'php:H:i'); ?></span>
                            <? if ($isFixed) : ?><i class="fas fa-thumbtack" title="Закреплено на это время"></i><? endif; ?>
                            <?= $task->reminderIconHtml; ?>
                            <?= $task->replannedIconHtml; ?>
                            <span><?= Html::encode($task->statusAsText); ?></span>
                        </div>
                        <a class="sx-task-deadlines__card-name" href="<?= Html::encode(\yii\helpers\Url::to(['/cms/admin-cms-task/view', 'pk' => $task->id])); ?>" data-sx-task-open="<?= Html::encode($url); ?>" data-pjax="0"><?= Html::encode($task->name); ?></a>
                        <? if ($client) : ?>
                            <span class="sx-task-deadlines__card-client"><?= Html::encode($client); ?></span>
                        <? endif; ?>
                    </div>
                <? endforeach; ?>
                <? if (!$column['tasks']) : ?>
                    <div class="sx-task-deadlines__empty"><?= $key === 'late' ? 'Просроченных нет' : 'Задач нет'; ?></div>
                <? endif; ?>
            </div>
            <? if (count($column['tasks']) > $widget->columnLimit) : ?>
                <div class="sx-task-deadlines__more">и ещё <?= count($column['tasks']) - $widget->columnLimit; ?> — в календаре</div>
            <? endif; ?>
        </div>
    <? endforeach; ?>
</div>
<?= Html::endTag('div'); ?>
