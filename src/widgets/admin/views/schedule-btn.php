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
/* @var $widget \common\modules\work\widgets\ScheduleBtnWidget */

$widget = $this->context;
$user = $widget->user;
$pjaxId = $widget->pjaxId;
$layout = $widget->layout;
$refreshUrl = \yii\helpers\Url::to([
    '/cms/ajax/schedule-control',
    'id' => $pjaxId,
    'layout' => $layout,
]);
\skeeks\cms\widgets\assets\CmsUserScheduleAsset::register($this);
?>

<? $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id'              => $pjaxId,
    'enablePushState' => false,
    'isBlock'         => false,
    'timeout'         => 100000,
    'options'         => [
        'class' => 'sx-schedule-pjax sx-schedule-layout-'.$layout,
        'data-sx-schedule-refresh' => 30000,
        'data-sx-schedule-url' => $refreshUrl,
    ],
]); ?>
<? $form = \yii\widgets\ActiveForm::begin([
    'options' => [
        'data-pjax' => 1,
        'class' => "my-auto",
    ],
]); ?>

<?php
$notEndCrmSchedule = $cmsUserSchedule->getIsNewRecord() ? null : $cmsUserSchedule;
?>

<? if ($notEndCrmSchedule) : ?>
    <? /* if ($user->notEndCrmSchedule->date == date("Y-m-d")) : */ ?>
    <? if (1 == 1) : ?>
        <input type="hidden" value="stop" name="action-type"/>
        <?
        $time = $notEndCrmSchedule->durationAsText;
        ?>
        <?= \yii\helpers\Html::button(\skeeks\cms\backend\helpers\BackendIcon::render('stop', ['size' => 14]).' '.\Yii::$app->formatter->asTime($notEndCrmSchedule->start_at,
                "short")." — <span class='sx-schedule-last'>".\Yii::$app->formatter->asTime(time(),
                "short")."</span>",
            [
                'class'          => 'btn btn-md btn-primary',
                'type'           => 'submit',
                'onclick'        => "$(this).tooltip('hide')",
                'title'          => 'Остановить работу. <br />В промежутке: '.$time,
                'data-toggle'    => 'tooltip',
                'data-html'      => 'true',
                'data-placement' => 'right',
            ]); ?>
    <? else : ?>

        <div class="sx-text--danger">
            Когда вы закончили работу?<br/>
            <?= \Yii::$app->formatter->asDate($user->notEndCrmSchedule->date); ?>
        </div>

        <input type="hidden" value="stop" name="action-type"/>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($cmsUserSchedule, 'start_time')->textInput([
                    'placeholder' => "11:30",
                    'disabled'    => "disabled",
                ])->label(false); ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($cmsUserSchedule, 'end_time')->textInput(['placeholder' => "11:30"])->label(false); ?>
            </div>
        </div>
        <?
        $time = $user->notEndCrmSchedule->durationAsText;
        ?>
        <?= \yii\helpers\Html::button(\skeeks\cms\backend\helpers\BackendIcon::render('stop', ['size' => 14]).' '.\Yii::$app->formatter->asTime($user->notEndCrmSchedule->start_time, "short")." — <span>?</span>",
            [
                'class'          => 'btn btn-md btn-primary sx-button sx-button--primary',
                'type'           => 'submit',
                'onclick'        => "$(this).tooltip('hide')",
                'title'          => 'Остановить работу. <br />В промежутке: '.$time,
                'data-toggle'    => 'tooltip',
                'data-html'      => 'true',
                'data-placement' => 'right',
            ]); ?>

    <? endif; ?>
<? else : ?>
    <input type="hidden" value="start" name="action-type"/>
    <?= \yii\helpers\Html::button(\skeeks\cms\backend\helpers\BackendIcon::render('play', ['size' => 18]).' Начать работу', [
        'class'          => 'btn btn-md btn-primary',
        'type'           => 'submit',
        'onclick'        => "$(this).tooltip('hide')",
        'title'          => 'Включить учет времени',
        'data-toggle'    => 'tooltip',
        'data-html'      => 'true',
        'data-placement' => 'right',
    ]); ?>
<? endif; ?>


<? /* print_r($cmsSchedule->errors); */ ?>
<?= $form->errorSummary($cmsUserSchedule, ['header' => false]); ?>

<? $form::end(); ?>
<!--</form>-->

<?
$cmsSchedulesByDate = \skeeks\cms\models\CmsUserSchedule::find()->user($user)->today()->all();

if ($cmsSchedulesByDate) : ?>


    <div class="my-auto">
        <a href="#" class="sx-schedule-info" data-toggle="tooltip" data-html="true"
           title="Сегодня: <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsTextBySchedules($cmsSchedulesByDate)."<br>".\skeeks\cms\helpers\CmsScheduleHelper::getAsTextBySchedules($cmsSchedulesByDate); ?>">
            <?= \skeeks\cms\backend\helpers\BackendIcon::render('info', ['size' => 15]); ?>
        </a>
    </div>

    <!--<div class="my-auto">
        <a href="#" >
            Сегодня: <? /*= \skeeks\crm\helpers\CrmScheduleHelper::durationAsTextBySchedules($cmsSchedulesByDate); */ ?>
        </a>
    </div>-->
<? endif; ?>

<? if ($error) : ?>
    <div class="col-md-12">
        <p class="sx-text--danger"><?= $error; ?></p>
    </div>
<? endif; ?>

<?php if($notEndCrmSchedule): ?>
    <div class="my-auto sx-current-task">
        <?php if ($currentTask = \skeeks\cms\models\CmsTask::find()->executor(\Yii::$app->user->identity)->statusInWork()->one()) : ?>
            <?php echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget([
                'task' => $currentTask,
                'isShowOnlyName' => true,
                'isShowStatus' => true,
            ]);
            ?>
        <?php else : ?>
            <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-task/calendar']); ?>" data-pjax="0" data-toggle="tooltip" title="У вас не запущена никакая задача, возьмите задачу в работу в этом разделе.">Выбрать задачу!</a>
        <?php endif; ?>
    </div>
<?php else : ?>
<div class="my-auto sx-current-task">
    <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-task/calendar']); ?>" data-pjax="0" data-toggle="tooltip" title="У вас не запущена работа. Нажмите на кнопку и начните работу. Система будет вести учет вашего рабочего времени.">Включите таймер!</a>
</div>
<?php endif; ?>

<? $pjax::end(); ?>

