<?php
/* @var $this yii\web\View */

$this->title = 'Система управления сайтом';
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
          'body' => \yii\helpers\Html::tag("div", 'Добро пожаловать! Вы находитесь в системе управления сайтом.'),
        ]);
    ?>

    <div class="body-content">

        <ul class="statistics">
            <li>
                <i class="icon-pie-chart"></i>
                <div class="number"><?= round($freeSpacePercent); ?>%</div>
                <div class="title">Свободное место</div>
                <div class="progress thin">
                    <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?= $freeSpacePercent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $freeSpacePercent; ?>%">
                        <span class="sr-only"><?= $freeSpacePercent; ?>% Complete (success)</span>
                    </div>
                </div>
            </li>

        <li>
            <i class="icon-users"></i>
            <div class="number"><?= count(\skeeks\cms\models\User::find()->all()); ?></div>
            <div class="title">Количество пользователей</div>

        </li>
    </ul>

<hr />
        <h2>Подробнее</h2>
        <p>Всего на сервере: <?= Yii::$app->formatter->asSize($freeSpace); ?></p>
        <p>Занято: <?= Yii::$app->formatter->asSize($usedSpace); ?></p>
        <p>Осталось: <?= Yii::$app->formatter->asSize($freeSpace); ?></p>
        <?

        $baseOptions =
        [
          'title' => ['text' => 'В процентном соотношении'],
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
                            ['Свободно', $freeSpacePercent],
                            ['Занято', $usedSpacePercent],
                      ]

              ],
          ]
       ];
        echo \skeeks\widget\highcharts\Highcharts::widget(['options' => $baseOptions]);

        ?>

    </div>
</div>
