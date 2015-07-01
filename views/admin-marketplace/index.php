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

if (CmsExtension::$coreExtensions)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-info-sign"></i> Ядро SkeekS CMS',
        'encode' => false,
        'content' => $this->render('_table-extensions', [
            'models' => CmsExtension::$coreExtensions,
            'message' => <<<HTML
                <p>Модули входящие в состав ядра SkeekS CMS не могут быть удалены, или же обновлены по отдельности.</p>
                <p>Вы так же, можете ознакомиться с версией установленного расширения, посмотреть его в маркетплейс.</p>
HTML

        ]),
    ];
}
if ($notCoreModels)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-plus-sign"></i> Дополнительные решения',
        'encode' => false,
        'content' => $this->render('_table-extensions', [
            'models' => $notCoreModels,
            'message' => <<<HTML
                <p>Дополнительные решения успешно установленные в вашем проекте.</p>
                <p>Эти решения можно удалять и обновлять.</p>
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




