<?php
/**
 * What the card of a task shows of its place in the queue: the planned end (overdue), the bell, «не успевает» in the
 * actions bar; the deadline, «Перенесена» and the reminder in the properties. The card renders it, and the pin answers
 * it (admin-cms-task/pin, `card`) so the card shows a new end or marks without a reload while its start stayed.
 * Every element carries data-sx-task-state="<part>": task-calendar.js replaces them.
 *
 * @var $model \skeeks\cms\models\CmsTask
 * @var $part string actions|properties
 */

use skeeks\cms\models\CmsTask;

$state = ['data-sx-task-state' => $part];
?>
<?php if ($part === 'actions') : ?>
    <?php
    // The deadline: the planned end; an open task past it is overdue.
    $deadline = $model->plan_end_at ? (int)$model->plan_end_at : null;
    $isClosed = in_array($model->status, [CmsTask::STATUS_READY, CmsTask::STATUS_CANCELED], true);
    $isOverdue = $deadline && !$isClosed && $deadline < time();
    ?>
    <?php if ($deadline) : ?>
        <?php echo \yii\helpers\Html::beginTag('span', $state + ['class' => 'sx-task-deadline'.($isOverdue ? ' sx-task-deadline--overdue' : ''), 'title' => 'Планируемое завершение']); ?>
            <?php echo \skeeks\cms\backend\helpers\BackendIcon::render('clock', ['size' => 14]); ?>
            <?php echo $isOverdue ? 'Просрочена: ' : 'Закончится: '; ?><?php echo \Yii::$app->formatter->asDatetime($deadline, 'php:d.m.Y H:i'); ?>
        </span>
    <?php endif; ?>
    <?php if ($model->hasAttribute('remind_at') && $model->remind_at) : ?>
        <?php echo \yii\helpers\Html::tag('span', $model->reminderIconHtml, $state + ['class' => 'sx-task-deadline']); ?>
    <?php endif; ?>
    <?php if ($model->isBehindDeadline()) : ?>
        <?php echo \yii\helpers\Html::tag('span', $model->deadlineMarkHtml, $state + ['class' => 'sx-task-deadline']); ?>
    <?php endif; ?>
<?php else : ?>
    <?php if ($model->deadlineText) : ?>
        <?php echo \yii\helpers\Html::beginTag('li', $state); ?>
            <span class="sx-properties--name">Крайний срок</span>
            <span class="sx-properties--value"><?php echo \Yii::$app->formatter->asDatetime((int)$model->deadline_at, 'php:d.m.Y H:i'); ?> <?php echo $model->deadlineMarkHtml; ?></span>
        </li>
    <?php endif; ?>
    <?php if ($model->replannedText) : ?>
        <?php echo \yii\helpers\Html::beginTag('li', $state); ?>
            <span class="sx-properties--name">Перенесена</span>
            <span class="sx-properties--value"><?php echo $model->replannedIconHtml; ?> <?php echo \yii\helpers\Html::encode($model->replannedText); ?></span>
        </li>
    <?php endif; ?>
    <?php if ($model->hasAttribute('remind_at') && $model->remind_at) : ?>
        <?php echo \yii\helpers\Html::beginTag('li', $state); ?>
            <span class="sx-properties--name">Напоминание</span>
            <span class="sx-properties--value"><?php echo $model->reminderIconHtml; ?> <?php echo \yii\helpers\Html::encode($model->reminderText); ?></span>
        </li>
    <?php endif; ?>
<?php endif; ?>
