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
?>

<? $this->registerJs(<<<JS
(function(sx, $, _)
{
sx.classes.InfoSchedule = sx.classes.Component.extend({

    _init: function()
    {
        var self = this;
        setInterval(function()
        {
            self.update();
        }, 30000);
    },

    update: function()
    {
        $.pjax.reload("#" + this.get('id'), {async:false});
    },

});

new sx.classes.InfoSchedule({
    'id': 'sx-schedule-pjax'
});
})(sx, sx.$, sx._);
JS
);
$this->registerCss(<<<CSS

#sx-schedule-pjax {
    display: flex;
}

#sx-schedule-pjax>div {
    margin-left: 1rem;
}

#sx-schedule-pjax .sx-current-task .sx-preview-card img.sx-photo {
    width: 2rem;
    height: 2rem;
}

#sx-schedule-pjax .sx-current-task .sx-main-info {
    overflow: hidden;
    max-height: 40px;
}
#sx-schedule-pjax .sx-current-task {
    line-height: 1;
    max-width: 30rem;
    max-height: 40px;
    
    margin-left: 2rem;
}

#sx-schedule-pjax .sx-current-task .sx-preview-card img.sx-photo {
    width: 2rem;
    height: 2rem;
}

.sx-schedule-last {
    animation: sx-pulse 3s infinite ease;
}
@keyframes sx-pulse {
    0%{
        opacity: 1;
        background: rgba(255, 48, 26, 0);
        box-shadow: none;
    }

    54%{
        background: rgba(255, 48, 26, 0);
        box-shadow: none;
        opacity: 0;
    }
    55%{
        opacity: 0;
        /*box-shadow: 0 0 9px 1px red, 0 0 10px 0px red;*/
        /*background: #5c5c5c;*/
    }
    100%{
        opacity: 1;
        /*box-shadow: 0 0 13px -9px red, 0 0 32px 20px red;*/
        /*background: rgba(255, 48, 26, 0);*/
    }
}
CSS
);
?>

<? $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id'              => 'sx-schedule-pjax',
    'enablePushState' => false,
    'isBlock'         => false,
    'timeout'         => 100000,
    'options'         => [
        'class' => '',
    ],
]); ?>
<? $form = \yii\widgets\ActiveForm::begin([
    'options' => [
        'data-pjax' => 1,
        'class' => "my-auto",
    ],
]); ?>

<?php
$notEndCrmSchedule = \skeeks\cms\models\CmsUserSchedule::find()->user($user)->notEnd()->one();
?>

<? if ($notEndCrmSchedule) : ?>
    <? /* if ($user->notEndCrmSchedule->date == date("Y-m-d")) : */ ?>
    <? if (1 == 1) : ?>
        <input type="hidden" value="stop" name="action-type"/>
        <?
        $time = $notEndCrmSchedule->durationAsText;
        ?>
        <?= \yii\helpers\Html::button('<i class="fa fa-stop" style="color: white;"></i> '.\Yii::$app->formatter->asTime($notEndCrmSchedule->start_at,
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

        <div style="color: red;">
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
        <?= \yii\helpers\Html::button('<i class="fa fa-stop"></i> '.\Yii::$app->formatter->asTime($user->notEndCrmSchedule->start_time, "short")." — <span style='color: silver;'>?</span>",
            [
                'class'          => 'btn btn-md u-btn-inset u-btn-inset--rounded u-btn-primary g-font-weight-600 g-letter-spacing-0_5 g-brd-2 g-rounded-50 g-mr-10',
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
    <?= \yii\helpers\Html::button('<i class="fa fa-play" style="color: white;"></i> Начать работу', [
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
        <a href="#" style="cursor: unset;" data-toggle="tooltip" data-html="true"
           title="Сегодня: <?php echo \skeeks\crm\helpers\CrmScheduleHelper::durationAsTextBySchedules($cmsSchedulesByDate)."<br>".\skeeks\cms\helpers\CmsScheduleHelper::getAsTextBySchedules($cmsSchedulesByDate); ?>">
            <i class="fas fa-info"></i>
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
        <p style="color: red;"><?= $error; ?></p>
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
            <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-task/index']); ?>" data-pjax="0" data-toggle="tooltip" title="У вас не запущена никакая задача, возьмите задачу в работу в этом разделе.">Выбрать задачу!</a>
        <?php endif; ?>
    </div>
<?php else : ?>
<div class="my-auto sx-current-task">
    <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-task/index']); ?>" data-pjax="0" data-toggle="tooltip" title="У вас не запущена работа. Нажмите на кнопку и начните работу. Система будет вести учет вашего рабочего времени.">Включите таймер!</a>
</div>
<?php endif; ?>

<? $pjax::end(); ?>

<? $this->registerJs(<<<JS
$("body").on('click', ".sx-total-link", function() {
    $('.sx-schedule-times').toggle();
    $(this).tooltip('hide');
    return false;
});
JS
); ?>



