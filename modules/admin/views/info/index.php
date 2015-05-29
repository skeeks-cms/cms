<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */

use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use Yii;

$autoModulesFile = (file_exists(AUTO_GENERATED_MODULES_FILE) ? "Да" : "Нет") . " <a class='btn btn-xs btn-default' title='" . AUTO_GENERATED_MODULES_FILE . "'>i</a>
<a class='btn btn-xs btn-primary' title='" . AUTO_GENERATED_MODULES_FILE . "' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/update-modules-file')->enableAdmin()->toString() . "'>Обновить</a>
";

$autoEnvFile = '';
if (file_exists(APP_ENV_GLOBAL_FILE))
{
    $autoEnvFile = 'Да ';
    $autoEnvFile .= "<a class='btn btn-xs btn-primary' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/remove-env-global-file')->enableAdmin()->toString() . "'>Удалить</a>  ";
} else
{
    $autoEnvFile = 'Нет ';
}
$autoEnvFile .= "<a class='btn btn-xs btn-primary' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/write-env-global-file', ['env' => 'dev'])->enableAdmin()->toString() . "'>Записать dev</a>  ";
$autoEnvFile .= "<a class='btn btn-xs btn-primary' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/write-env-global-file', ['env' => 'prod'])->enableAdmin()->toString() . "'>Записать prod</a>";

?>
<? $form = ActiveForm::begin(); ?>

<?= $form->fieldSet('Конфигурация проекта'); ?>
    <?php
    echo $this->render('table', [
        'values' => [
            \Yii::$app->cms->moduleCms()->getName() => \Yii::$app->cms->moduleCms()->descriptor->version,
            'Yii Version' => $application['yii'],
            'Название проекта' => $application['name'] . " (<a href='" . \skeeks\cms\helpers\UrlHelper::construct('cms/admin-settings')->enableAdmin()->toString() . "'>изменить</a>)",
            'Окружение (YII_ENV)' => $application['env'],
            'Режим разработки (YII_DEBUG)' => $application['debug'] ? 'Да' : 'Нет',
            'Режим автогенерации путей к модулям CMS (ENABLE_MODULES_CONF)' => ENABLE_MODULES_CONF ? 'Да' : 'Нет',
            'Автоматически сгенерированный файл с путями модулей cms (AUTO_GENERATED_MODULES_FILE)' => $autoModulesFile,
            'Папка с файлом путей к модулям' => (is_readable(dirname(AUTO_GENERATED_MODULES_FILE)) ? "Да" : "Не доступна для записи") . " <a class='btn btn-xs btn-default' title='" . dirname(AUTO_GENERATED_MODULES_FILE) . "'>i</a>",
            'Проверяются переменные окружения (GETENV_POSSIBLE_NAMES)' => GETENV_POSSIBLE_NAMES,
            'Проверяются переменные окружения (APP_ENV_GLOBAL_FILE)' => $autoEnvFile . " <a class='btn btn-xs btn-default' title='" . APP_ENV_GLOBAL_FILE . "'>i</a>"

            ,
        ],
    ]);
    ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Установленные модули CMS'); ?>
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
?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Все расширения и модули Yii'); ?>
    <?if (!empty($extensions)) {
        echo $this->render('table', [
            'values' => $extensions,
        ]);
    }?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('PHP конфигурация'); ?>
    <?
    echo $this->render('table', [
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
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('PHP info'); ?>
    <iframe id="php-info" src='<?= \skeeks\cms\helpers\UrlHelper::construct('/admin/info/php')->enableAdmin()->toString(); ?>' width='100%' height='1000'></iframe>;
<?= $form->fieldSetEnd(); ?>

<? ActiveForm::end(); ?>




