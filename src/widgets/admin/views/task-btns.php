<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $error string */
/* @var $widget \skeeks\crm\widgets\TaskBtnsWidget */
/* @var $crmTaskSchedule \skeeks\cms\models\CmsTaskSchedule */

$widget = $this->context;
$user = $widget->user;

$accepted = \skeeks\cms\models\CmsTask::STATUS_ACCEPTED;
$canceled = \skeeks\cms\models\CmsTask::STATUS_CANCELED;
$work = \skeeks\cms\models\CmsTask::STATUS_IN_WORK;
$on_check = \skeeks\cms\models\CmsTask::STATUS_ON_CHECK;
$on_pause = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE;
$on_ready = \skeeks\cms\models\CmsTask::STATUS_READY;

$this->registerCss(<<<CSS
    .btn-task {
        color: white;
        border-radius: var(--border-radius);
        margin-right: 0.25rem;
    }
    .btn-task:hover {
        color: white;
    }
    
    .btn-task-{$accepted} {
        background: #9a69cb;
    }
    
    .btn-task-{$canceled} {
        background: var(--color-gray);
    }
    
    .btn-task-{$work} {
        background-color: #22e3be;
    }
    
    .btn-task-{$on_check} {
        background-color: #00bed6;
    }
    
    .btn-task-{$on_pause} {
        background-color: #e57d20;
    }
    .btn-task-{$on_ready} {
        background-color: green;
    }
CSS
);
?>

<? if ($widget->isPjax) : ?>
    <? $pjax = \skeeks\cms\widgets\Pjax::begin([
        'id'              => 'sx-pjax-'.$widget->id,
        'enablePushState' => false,
        'isBlock'         => false,
        'timeout'         => 10000,
        'options'         => [
            'class' => 'g-color-gray-dark-v6',
        ],
    ]); ?>
<? endif; ?>

<div class="sx-task-btns">
<?
if ($isSaved) {
    $this->registerJs(<<<JS
sx.Window.openerWidgetTriggerEvent('model-create', {
    'submitBtn' : 'apply'
});
JS
    );
}

$form = \yii\widgets\ActiveForm::begin([
    'id'      => "form-".$widget->id,
    'options' => [
        'data-pjax' => 1,
    ],
]);

$title = '';

?>

<div style="display: none;">
    <input type="hidden" name="<?= $widget->id; ?>" value="1"/>
    <?= $form->field($widget->task, "status")->textInput(['class' => 'sx-task-status']); ?>
</div>

<? if ($widget->task->executor_id == \Yii::$app->user->id) : ?>

    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_NEW) : ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_ACCEPTED; ?>
        <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_ACCEPTED).'"></i> Взять задачу',
            [
                'class'       => 'btn btn-task btn-task-' . \skeeks\cms\models\CmsTask::STATUS_ACCEPTED,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide');  $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ACCEPTED).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

    <? endif; ?>

    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_ACCEPTED) : ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_IN_WORK; ?>
        <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_IN_WORK).'"></i> Начать работу',
            [
                'class'       => 'btn btn-task btn-task-' . \skeeks\cms\models\CmsTask::STATUS_IN_WORK,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_IN_WORK).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

    <? endif; ?>


    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_IN_WORK) : ?>

        <?/* if ($widget->task->notEndCrmTaskSchedule && $widget->task->notEndCrmTaskSchedule->date != date("Y-m-d")) : */?><!--

            <div class="row">
                <div class="col-md-12" style="color: red;">
                Когда была завершена задача <?/*= \Yii::$app->formatter->asDate($widget->task->notEndCrmTaskSchedule->date); */?>?
            </div>
            <div class="col-md-3">

                <?/*= $form->field($crmTaskSchedule, 'end_time')->textInput([
                    'placeholder' => '11:30'
                ])->label(false); */?>
            </div>
            <div class="col-md-3">
                <?/* $status = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE; */?>
                <?/*= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).'"></i> На паузу',
                    [
                        'class'       => 'btn btn-task '.\yii\helpers\ArrayHelper::getValue(\skeeks\cms\models\CmsTask::statusesColors(),
                                \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE),
                        'type'        => 'submit',
                        'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                        'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).$title,
                        'data-toggle' => 'tooltip',
                        'data-html'   => 'true',
                    ]); */?>

            </div>
            </div>
        --><?/* else : */?>
            <? $status = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE; ?>
            <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).'"></i> На паузу',
                [
                    'class'       => 'btn btn-task btn-task-'.\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE,
                    'type'        => 'submit',
                    'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                    'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).$title,
                    'data-toggle' => 'tooltip',
                    'data-html'   => 'true',
                ]); ?>

    <?php if($task->executor_id == $task->created_by) : ?>
        <? $status = \skeeks\cms\models\CmsTask::STATUS_READY; ?>
            <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_READY).'"></i> Готова',
            [
                'class'       => 'btn btn-task btn-task-'. \skeeks\cms\models\CmsTask::STATUS_READY,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_READY).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

    <?php else : ?>
        <? $status = \skeeks\cms\models\CmsTask::STATUS_ON_CHECK; ?>
            <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_ON_CHECK).'"></i> На проверку',
                [
                    'class'       => 'btn btn-task btn-task-'.\skeeks\cms\models\CmsTask::STATUS_ON_CHECK,
                    'type'        => 'submit',
                    'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                    'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ON_CHECK).$title,
                    'data-toggle' => 'tooltip',
                    'data-html'   => 'true',
                ]); ?>
    <?php endif; ?>




        <?/* endif; */?>


    <? endif; ?>

    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE) : ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_IN_WORK; ?>
        <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_IN_WORK).'"></i> Начать работу',
            [
                'class'       => 'btn btn-task btn-task-'.\skeeks\cms\models\CmsTask::STATUS_IN_WORK,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_IN_WORK).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

    <? endif; ?>

<? endif; ?>

<? if ($widget->task->created_by == \Yii::$app->user->id || $widget->task->executor_id == \Yii::$app->user->id) : ?>
    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_NEW || $widget->task->status == \skeeks\cms\models\CmsTask::STATUS_ACCEPTED) : ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_CANCELED; ?>
        <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_CANCELED).'"></i> Отменить',
            [
                'class'       => 'btn btn-task btn-task-'.\skeeks\cms\models\CmsTask::STATUS_CANCELED,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_CANCELED).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

    <? endif; ?>

    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_CANCELED) : ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE; ?>
        <?= \yii\helpers\Html::button('<i class="fas fa-redo"></i> Возобновить',
        [
            'class'       => 'btn btn-task btn-task-' . \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE,
            'type'        => 'submit',
            'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
            'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).$title,
            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
        ]); ?>

    <? endif; ?>

<? endif; ?>

<? if ($widget->task->created_by == \Yii::$app->user->id) : ?>
    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_ON_CHECK) : ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE; ?>
        <?= \yii\helpers\Html::button('<i class="fas fa-redo"></i> Возобновить',
            [
                'class'       => 'btn btn-task btn-task-' . \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

        <? $status = \skeeks\cms\models\CmsTask::STATUS_READY; ?>
        <?= \yii\helpers\Html::button('<i class="'.\skeeks\cms\models\CmsTask::statusesIcons(\skeeks\cms\models\CmsTask::STATUS_READY).'"></i> Готова',
            [
                'class'       => 'btn btn-task btn-task-'. \skeeks\cms\models\CmsTask::STATUS_READY,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_READY).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>

    <? endif; ?>

    <? if ($widget->task->status == \skeeks\cms\models\CmsTask::STATUS_READY) : ?>
        <? $status = \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE; ?>
        <?= \yii\helpers\Html::button('<i class="fas fa-redo"></i> Возобновить',
            [
                'class'       => 'btn btn-task btn-task-' . \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE,
                'type'        => 'submit',
                'onclick'     => "$(this).tooltip('hide'); $('.sx-task-status', '#form-{$widget->id}').val('{$status}');",
                'title'       => \skeeks\cms\models\CmsTask::statusesFeatureHints(\skeeks\cms\models\CmsTask::STATUS_ON_PAUSE).$title,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ]); ?>
    <? endif; ?>
<? endif; ?>


<?= $form->errorSummary($task, ['header' => '']); ?>

<? $form::end(); ?>
<!--</form>-->

</div>
<? if ($widget->isPjax) : ?>
    <? $pjax::end(); ?>
<? endif; ?>




