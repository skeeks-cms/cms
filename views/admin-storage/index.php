<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.04.2015
 */
?>
<h2>Сервера для хранения файлов.</h2>
<? foreach(\Yii::$app->storage->getClusters() as $count => $cluster) : ?>
<div class="sx-box sx-p-10 sx-bg-primary">
    <div class="row">
        <div class="col-md-12">
            <h3><b><?= $count+1; ?>. <?= $cluster->name; ?></b></h3>
            <hr />
        </div>
        <div class="col-md-4">
            <p><b>Публичный путь к файлам: </b> <?= $cluster->publicBaseUrl; ?></p>
            <p><b>Папка на сервере: </b> <?= $cluster->rootBasePath; ?></p>

            <p><b>Всего доступно места</b>: <?= Yii::$app->formatter->asShortSize($cluster->getTotalSpace()); ?></p>
            <p><b>Занято</b>: <?= Yii::$app->formatter->asShortSize($cluster->getUsedSpace()); ?></p>
            <p><b>Еще свободно</b>: <?= Yii::$app->formatter->asShortSize($cluster->getFreeSpace()); ?></p>

            <? if ($cluster instanceof \skeeks\cms\components\storage\ClusterLocal) : ?>
                <? if ($cluster->publicBaseUrlIsAbsolute) : ?>
                    <p><b>Файлы раздаются с домена: </b> <?= $cluster->publicBaseUrlIsAbsolute; ?></p>
                <? endif; ?>
            <? endif; ?>
        </div>
        <div class="col-md-3">
            <ul class="statistics">
                <li>
                    <i class="icon-pie-chart"></i>
                    <div class="number"><?= round($cluster->getFreeSpacePct()); ?>%</div>
                    <div class="title">Свободное место</div>
                    <div class="progress thin">
                        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?= $cluster->getFreeSpacePct(); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $cluster->getFreeSpacePct(); ?>%">
                            <span class="sr-only"><?= round($cluster->getFreeSpacePct()); ?>% Complete (success)</span>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="col-md-3">
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
                                    ['Свободно', round($cluster->getFreeSpacePct())],
                                    ['Занято', round($cluster->getUsedSpacePct())],
                              ]

                      ],
                  ]
               ];
                echo \skeeks\widget\highcharts\Highcharts::widget(['options' => $baseOptions]);

                ?>
        </div>
    </div>
</div>
<? endforeach; ?>
