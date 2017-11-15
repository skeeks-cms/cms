<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 13.04.2016
 */
/* @var $this yii\web\View */
$this->title = 'Система управления сайтом: SkeekS CMS (Yii2)';
?>

<div style="text-align: center; padding: 100px;">
    <p>Система управления сайтом: <?= \yii\helpers\Html::a("SkeekS CMS (Yii2)", \Yii::$app->cms->descriptor->homepage, [
            'target' => '_blank'
        ]); ?></p>
    <p>@author <?= \yii\helpers\Html::a("SkeekS", "https://skeeks.com", [
            'target' => '_blank'
        ]); ?></p>
</div>

