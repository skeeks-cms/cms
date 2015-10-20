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
<a class='btn btn-xs btn-primary' title='" . AUTO_GENERATED_MODULES_FILE . "' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/update-modules-file')->enableAdmin()->toString() . "'>".\Yii::t('app','Update')."</a>
";

$autoEnvFile = '';
if (file_exists(APP_ENV_GLOBAL_FILE))
{
    $autoEnvFile = \Yii::t('app','Yes').' ';
    $autoEnvFile .= "<a class='btn btn-xs btn-primary' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/remove-env-global-file')->enableAdmin()->toString() . "'>".\Yii::t('app','Delete')."</a>  ";
} else
{
    $autoEnvFile = \Yii::t('app','No').' ';
}
$autoEnvFile .= "<a class='btn btn-xs btn-primary' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/write-env-global-file', ['env' => 'dev'])->enableAdmin()->toString() . "'>".\Yii::t('app', 'To record')." dev</a>  ";
$autoEnvFile .= "<a class='btn btn-xs btn-primary' href='" . \skeeks\cms\helpers\UrlHelper::construct('admin/info/write-env-global-file', ['env' => 'prod'])->enableAdmin()->toString() . "'>".\Yii::t('app', 'To record')." prod</a>";

?>
<? $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('app','Project configuration')); ?>
    <?php
    echo $this->render('table', [
        'values' => [
            \Yii::$app->cms->moduleCms()->getName() => \Yii::$app->cms->moduleCms()->descriptor->version,
            \Yii::t('app','{yii} Version', ['yii' => 'Yii']) => $application['yii'],
            \Yii::t('app','Project name') => $application['name'] . " (<a href='" . \skeeks\cms\helpers\UrlHelper::construct('cms/admin-settings')->enableAdmin()->toString() . "'>".\Yii::t('app','edit')."</a>)",
            \Yii::t('app','Environment ({yii_env})',['yii_env' => 'YII_ENV']) => $application['env'],
            \Yii::t('app','Development mode ({yii_debug})',['yii_debug' => 'YII_DEBUG']) => $application['debug'] ? \Yii::t('app','Yes') : \Yii::t('app','No'),
            \Yii::t('app','Mode autogeneration paths to modules {cms} ({e_m_c})',['e_m_c' => 'ENABLE_MODULES_CONF', 'cms' => 'CMS']) => ENABLE_MODULES_CONF ? \Yii::t('app','Yes') : \Yii::t('app','No'),
            \Yii::t('app','Automatically generated file paths modules {cms} ({agmf})',['cms' => 'cms', 'agmf' => 'AUTO_GENERATED_MODULES_FILE']) => $autoModulesFile,
            \Yii::t('app','A folder with file paths to the modules') => (is_readable(dirname(AUTO_GENERATED_MODULES_FILE)) ? \Yii::t('app','Yes') : \Yii::t('app',"Is not writable")) . " <a class='btn btn-xs btn-default' title='" . dirname(AUTO_GENERATED_MODULES_FILE) . "'>i</a>",
            \Yii::t('app',"Checks environment variables").' (GETENV_POSSIBLE_NAMES)' => GETENV_POSSIBLE_NAMES,
            \Yii::t('app',"Checks environment variables").' (APP_ENV_GLOBAL_FILE)' => $autoEnvFile . " <a class='btn btn-xs btn-default' title='" . APP_ENV_GLOBAL_FILE . "'>i</a>"

            ,
        ],
    ]);
    ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Installed {cms} modules',['cms' => 'CMS'])); ?>
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
                'label'     => \Yii::t('app','Module name')
            ],

            [
                'attribute' => 'version',
                'label'     => \Yii::t('app','Module version')
            ],
        ]
    ]);
?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('app','All extensions and modules {yii}',['yii' => 'Yii'])); ?>
    <?if (!empty($extensions)) {
        echo $this->render('table', [
            'values' => $extensions,
        ]);
    }?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('app','{php} configuration',['php' => "PHP"])); ?>
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




