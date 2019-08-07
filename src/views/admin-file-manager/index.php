<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.07.2016
 */
?>

<?
echo \mihaildev\elfinder\ElFinder::widget([
    'controller' => 'cms/admin-elfinder-full', // вставляем название контроллера, по умолчанию равен elfinder
    'callbackFunction' => new \yii\web\JsExpression('function(file, id){}'), // id - id виджета
    'frameOptions' => [
        'style' => 'width: 100%; height: 800px;'
    ]
]);
?>