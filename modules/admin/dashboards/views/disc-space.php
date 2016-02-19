<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.02.2016
 */
/* @var $this yii\web\View */

?>

<?
$freeSpace  = (float) disk_free_space("/");
$totalSpace = (float) disk_total_space("/");
$usedSpace = $totalSpace - $freeSpace;

$freeSpacePercent = ($freeSpace * 100) / $totalSpace;
$usedSpacePercent = 100 - $freeSpacePercent;
?>

<div class="col-md-12">

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
                            [\Yii::t('app','Free'), round($freeSpacePercent, 2)],
                            [\Yii::t('app','Used'), round($usedSpacePercent, 2)],
                      ]

              ],
          ]
       ];
        echo \skeeks\widget\highcharts\Highcharts::widget(['options' => $baseOptions]);
    ?>
    <hr />
    <p><b><?= \Yii::t('app','Total at server')?>:</b> <?= Yii::$app->formatter->asShortSize($totalSpace); ?></p>
    <p><b><?= \Yii::t('app','Used')?>:</b> <?= Yii::$app->formatter->asShortSize($usedSpace); ?></p>
    <p><b><?= \Yii::t('app','Free')?>:</b> <?= Yii::$app->formatter->asShortSize($freeSpace); ?></p>

</div>


