<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var $model CmsExtension */


use \skeeks\cms\components\marketplace\models\PackageModel;
use \skeeks\cms\models\CmsExtension;
$self = $this;
$models = CmsExtension::fetchAllWhithMarketplace();
CmsExtension::initCoreExtensions();

$notCoreModels = [];
foreach ($models as $name => $model)
{
    if (!$model->isCore())
    {
        $notCoreModels[] = $model;
    }
}
$mess = \Yii::t('app','The modules included in the kernel {cms} can not be deleted or updated individually.',['cms' => 'SkeekS CMS']);
$mess2 = \Yii::t('app','You also can read the version of the installed extensions, see it in the {market}',['market' => 'Marketplace']);

if (CmsExtension::$coreExtensions)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-info-sign"></i> '.\Yii::t('app','Core {cms}',['cms' => 'SkeekS CMS']),
        'encode' => false,
        'content' => $this->render('_table-extensions', [
            'models' => CmsExtension::$coreExtensions,
            'message' => <<<HTML
                <p>{$mess}</p>
                <p>{$mess2}</p>
HTML

        ]),
    ];
}

$mess3 = \Yii::t('app','Additional solutions, successfully installed in your project.');
$mess4 = \Yii::t('app','These solutions can be removed and updated.');
if ($notCoreModels)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-plus-sign"></i> '.\Yii::t('app','Additional solutions'),
        'encode' => false,
        'content' => $this->render('_table-extensions', [
            'models' => $notCoreModels,
            'message' => <<<HTML
                <p>{$mess3}</p>
                <p>{$mess4}</p>
HTML

        ]),
    ];
}
?>

<? if ($items) : ?>
    <?= \yii\bootstrap\Tabs::widget([
        'items' => $items
    ]); ?>
<? endif; ?>




