<?php
/* @var $this yii\web\View */

$this->title = \Yii::t('app','The site managment system');
use yii\bootstrap\Alert;



$freeSpace  = (float) disk_free_space("/");
$totalSpace = (float) disk_total_space("/");
$usedSpace = $totalSpace - $freeSpace;

$freeSpacePercent = ($freeSpace * 100) / $totalSpace;
$usedSpacePercent = 100 - $freeSpacePercent;

?>

<div class="site-index">

    <?=
        Alert::widget([
            'options' => [
              'class' => 'alert-info',
          ],
          'body' => \yii\helpers\Html::tag("div", \Yii::t('app','Welcome! You are in the site management system.')),
        ]);
    ?>

    <div class="body-content">

        <ul class="statistics">
            <li>
                <i class="icon-pie-chart"></i>
                <div class="number"><?= round($freeSpacePercent); ?>%</div>
                <div class="title"><?=\Yii::t('app','Free place')?></div>
                <div class="progress thin">
                    <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?= $freeSpacePercent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $freeSpacePercent; ?>%">
                        <span class="sr-only"><?= $freeSpacePercent; ?>% Complete (success)</span>
                    </div>
                </div>
            </li>

        <li>
            <i class="icon-users"></i>
            <div class="number"><a href="<?= \skeeks\cms\helpers\UrlHelper::construct('/cms/admin-user')->enableAdmin()->toString(); ?>"><?= \skeeks\cms\models\User::find()->count(); ?></a></div>
            <div class="title"><?=\Yii::t('app','Number of users')?></div>

        </li>
    </ul>

<hr />
    <div class="col-md-12">
        <h2><?= \Yii::t('app','Read more')?></h2>
        <p><?= \Yii::t('app','Total at server')?>: <?= Yii::$app->formatter->asShortSize($totalSpace); ?></p>
        <p><?= \Yii::t('app','Used')?>: <?= Yii::$app->formatter->asShortSize($usedSpace); ?></p>
        <p><?= \Yii::t('app','Free')?>: <?= Yii::$app->formatter->asShortSize($freeSpace); ?></p>
        <?

        $baseOptions =
        [
          'title' => ['text' => \Yii::t('app','At percent ratio')],
          'chart' => [
              'type' => 'pie',

          ],
           'plotOptions' =>
           [
               'pie' =>
               [
                    'allowPointSelect' => 'true',
                    'cursor' => "pointer",
                    'depth' => 35,
                   'dataLabels' =>
                   [
                       'enabled' => 'true',
                       'format' => '{point.name}',
                   ]
               ]
           ],
          'series' => [
              [
                  'type'=> 'pie',
                  'name'=> '%',
                  'data'=>
                      [
                            [\Yii::t('app','Free'), $freeSpacePercent],
                            [\Yii::t('app','Used'), $usedSpacePercent],
                      ]

              ],
          ]
       ];
        echo \skeeks\widget\highcharts\Highcharts::widget(['options' => $baseOptions]);

        ?>

    </div>
    </div>
</div>
