<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
use Yii;
?>

<? if (ENABLE_MODULES_CONF) : ?>
    <? if (file_exists(AUTO_GENERATED_MODULES_FILE)) : ?>

    <? endif; ?>
<? endif; ?>

<?php

echo $this->render('table', [
    'caption' => 'Конфигурация приложения',
    'values' => [
        \Yii::$app->cms->moduleCms()->getName() => \Yii::$app->cms->moduleCms()->getDescriptor()->getVersion(),
        'Yii Version' => $application['yii'],
        'Application Name' => $application['name'],
        'Environment' => $application['env'],
        'Debug Mode' => $application['debug'] ? 'Yes' : 'No',
        'Режим автогенерации путей к модулям CMS' => ENABLE_MODULES_CONF ? 'Yes' : 'No',
        'Автоматически сгенерированный файл с путями модулей cms' => AUTO_GENERATED_MODULES_FILE . " " . (file_exists(AUTO_GENERATED_MODULES_FILE) ? "Существует" : "Не сгенерирован"),
        'Папка с файлом путей к модулям' => dirname(AUTO_GENERATED_MODULES_FILE) . " " . (is_readable(dirname(AUTO_GENERATED_MODULES_FILE)) ? "Доступна для записи" : "Не доступна для записи"),
    ],
]);
?>
<h3>Установленные модули</h3>
<?
echo \skeeks\cms\modules\admin\widgets\GridView::widget([
    'dataProvider' => new \yii\data\ArrayDataProvider([
        'allModels' => \Yii::$app->cms->getModules(),
    ]),
    'layout'    => "{items}",
    'columns' => [

        //['class' => 'yii\grid\SerialColumn'],

        [
            'attribute' => 'name',
            'label'     => 'Название модуля'
        ],

        [
            'attribute' => 'version',
            'label'     => 'Версия модуля'
        ],
    ]
]);

if (!empty($extensions)) {
    echo $this->render('table', [
        'caption' => 'Установленные расширения и модули Yii',
        'values' => $extensions,
    ]);
}

echo $this->render('table', [
    'caption' => 'PHP конфигурация',
    'values' => [
        'PHP Version' => $php['version'],
        'Xdebug' => $php['xdebug'] ? 'Enabled' : 'Disabled',
        'APC' => $php['apc'] ? 'Enabled' : 'Disabled',
        'Memcache' => $php['memcache'] ? 'Enabled' : 'Disabled',
        'Xcache' => $php['xcache'] ? 'Enabled' : 'Disabled',
        'Gd' => $php['gd'] ? 'Enabled' : 'Disabled',
        'Imagick' => $php['imagick'] ? 'Enabled' : 'Disabled',
        'Sendmail Path' => ini_get('sendmail_path'),
        'Sendmail From' => ini_get('sendmail_from'),
        'open_basedir' => ini_get('open_basedir'),
        'realpath_cache_size' => ini_get('realpath_cache_size'),
        'xcache.cacher' => ini_get('xcache.cacher'),
        'xcache.ttl' => ini_get('xcache.ttl'),
        'xcache.stat' => ini_get('xcache.stat'),
        'xcache.size' => ini_get('xcache.size'),
    ],
]);
?>

<h3>PHP конфигурация расширенная</h3>
<iframe id="php-info" src='<?= \skeeks\cms\helpers\UrlHelper::construct('/admin/info/php')->enableAdmin()->toString(); ?>' width='100%' height='800'></iframe>;
