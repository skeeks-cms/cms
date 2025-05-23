<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$start = null;
$end = null;

if ($period = \Yii::$app->request->get("period")) {

    $data = explode("-", $period);
    $start = strtotime(trim(\yii\helpers\ArrayHelper::getValue($data, 0)." 00:00:00"));
    $end = strtotime(trim(\yii\helpers\ArrayHelper::getValue($data, 1)." 23:59:59"));
}

?>


    <div class="sx-block">
        <? $form = \yii\bootstrap\ActiveForm::begin([
            'method' => "get",
        ]); ?>
        <div class="btn-group">
            <?php echo \skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget::widget([
                'name'    => 'period',
                'value'   => $period,
                'options' => [
                    'placeholder' => 'Период отчета',
                    'class'       => 'form-control',
                ],
            ]); ?>
            <button type="submit" class="btn btn-primary">Применить</button>
        </div>
        <?php $form::end(); ?>
    </div>


<?php if ($start) : ?>
    <?php


    //Сколько всего человек отработал за период
    $qSchedules = \skeeks\cms\models\CmsUserSchedule::find()->user($model);
    if ($start) {
        $qSchedules->andWhere(['>=', 'start_at', $start]);
    }
    if ($end) {
        $qSchedules->andWhere(['<=', 'end_at', $end]);
    }

    $qUserFactSchedules = clone $qSchedules;
    $qSchedules->addSelect([
        "totalTime" => new \yii\db\Expression("SUM( (end_at - start_at) )"),
    ]);
    $userSchedule = $qSchedules->one();
    $userTotalTimeFact = 0;
    if ($userSchedule) {
        $userTotalTimeFact = \yii\helpers\ArrayHelper::getValue($userSchedule->raw_row, "totalTime");
    }


    $allFactSchedules = $qUserFactSchedules->all();


    $days = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesDays($start, $end);
    $planDurations = 0;

    foreach ($days as $day) {
        $planSchedules = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate($model->work_shedule, $day);
        $planDurations = $planDurations + \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($planSchedules);
    }

    ?>

    <div class="sx-block">
        <div class="sx-properties-wrapper sx-columns-1">
            <ul class="sx-properties">
                <li>
                    <span class="sx-properties--name">
                        Необходимо отработат по графику
                    </span>
                    <span class="sx-properties--value">
                        <?php if ($planDurations) : ?>
                            <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($planDurations); ?>
                        <?php endif; ?>

                    </span>
                </li>

                <li>
                <span class="sx-properties--name">
                    Отработано фактически
                </span>
                    <span class="sx-properties--value">
                    <?php if ($userTotalTimeFact) : ?>
                        <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($userTotalTimeFact); ?>
                    <?php endif; ?>

                </span>
                </li>
            </ul>
        </div>
    </div>

    <?php if ($allFactSchedules) : ?>
        <?php
        $this->registerCss(<<<CSS
.sx-user-fact-blocks {
    display: flex;
    flex-wrap: wrap;
}
CSS
        );
        ?>
        <div class="row">
            <?php
            /**
             * @var $userFactSchedule \skeeks\cms\models\CmsUserSchedule
             */
            foreach ($days as $day) : ?>
                <?
                    $dayStart = strtotime($day." 00:00:00");
                    $dayEnd = strtotime($day." 23:59:59");

                    $userTimesOnDay = \skeeks\cms\models\CmsUserSchedule::find()
                        ->user($model)
                        ->andWhere([
                            'or',
                            [
                                'and',
                                ['>=', 'start_at', $dayStart],
                                ['<=', 'start_at', $dayEnd]
                            ],
                            [
                                'and',
                                ['>=', 'end_at', $dayStart],
                                ['<=', 'end_at', $dayEnd]
                            ]
                        ])
                        ->all()
                    ;


                    $times = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate($model->work_shedule, $day);
                    $planTime = 0;
                    if ($times) {
                        $planTime = \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($times);
                    }
                ?>
                <div class="col-md-3">
                    <div class="sx-block">
                        <div class="sx-title">
                            <b><?php echo \Yii::$app->formatter->asDate($day); ?></b>
                            <span style="color: var(--color-gray);">
                            <?php if(!$times) : ?>
                                <span data-toggle="tooltip" title="По плану согласно рабочему графику сотрудника">(выходной)</span>
                            <?php else : ?>
                                <span data-toggle="tooltip" title="По плану согласно рабочему графику сотрудника">(<?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($planTime); ?>)</span>
                            <?php endif; ?>
                            </span>
                        </div>
                        <div class="sx-info">
                            <?php if($userTimesOnDay) : ?>
                                <?php



                                /**
                                 * @var $sheduleUserFactOnDay \skeeks\cms\models\CmsUserSchedule
                                 */
                                foreach($userTimesOnDay as $sheduleUserFactOnDay) : ?>


                                    <?php
                                    $start = "";
                                    $end = "сейчас...";

                                    if (date("Y-m-d", $sheduleUserFactOnDay->start_at) == $day)  {
                                        $start = \Yii::$app->formatter->asTime((int) $sheduleUserFactOnDay->start_at, "short");
                                    } else {
                                        $start = \Yii::$app->formatter->asDate((int) $sheduleUserFactOnDay->start_at, "short") . " " . \Yii::$app->formatter->asTime((int) $sheduleUserFactOnDay->start_at, "short");
                                    }

                                    if ($sheduleUserFactOnDay->end_at)  {
                                        if(date("Y-m-d", $sheduleUserFactOnDay->end_at) == $day)  {
                                            $end = \Yii::$app->formatter->asTime((int) $sheduleUserFactOnDay->end_at, "short");
                                        } else {
                                            $end = \Yii::$app->formatter->asDate((int) $sheduleUserFactOnDay->end_at, "short") . " " . \Yii::$app->formatter->asTime((int) $sheduleUserFactOnDay->end_at, "short");
                                        }
                                    }
                                    ?>


                                    <p>
                                        <?php echo $start; ?> — <?php echo $end; ?>
                                    </p>
                                <?php endforeach; ?>
                                
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else : ?>
    <div class="sx-block">
        Для построения отчета, укажите период!
    </div>
<?php endif; ?>