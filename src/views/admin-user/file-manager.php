<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.02.2015
 * @since 1.0.0
 */
?>

<?
echo \mihaildev\elfinder\ElFinder::widget([
    'language'         => 'ru',
    'controller'       => 'cms/elfinder-user-files', // вставляем название контроллера, по умолчанию равен elfinder
    //'filter'           => 'image',    // фильтр файлов, можно задать массив фильтров https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#wiki-onlyMimes
    'callbackFunction' => new \yii\web\JsExpression('function(file, id){}'), // id - id виджета
    'frameOptions' => [
        'style' => 'width: 100%; height: 800px;'
    ]
]);

/*echo \mihaildev\elfinder\InputFile::widget([
    'language'   => 'ru',
    'controller' => 'cms/elfinder', // вставляем название контроллера, по умолчанию равен elfinder
    'filter'     => 'image',    // фильтр файлов, можно задать массив фильтров https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#wiki-onlyMimes
    'name'       => 'myinput',
    'value'      => '',
]);*/
?>