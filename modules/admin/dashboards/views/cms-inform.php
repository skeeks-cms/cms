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

<div class="site-index">
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

    </div>
</div>


