<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var $packageModel PackageModel */

use \skeeks\cms\components\marketplace\models\PackageModel;
use \skeeks\cms\models\CmsExtension;
$self = $this;

?>

<? if ($code = \Yii::$app->request->get("code")) : ?>
    <div class="sx-box sx-p-10 sx-mb-10">

        <? if ($packageModel = PackageModel::fetchByCode($code)) : ?>

             <?= $this->render('catalog-package', [
                'packageModel' => $packageModel
            ])?>

        <? else: ?>
            Расширение не найдено
        <? endif; ?>

    </div>
<? else : ?>
    <div class="sx-box sx-p-10 sx-mb-10 sx-bg-primary">
        <p>В этом разделе показаны все расширения, которые успешно установлены и используются в вашем проекте.</p>
        <p>Вы так же, можете ознакомиться с версией установленного расширения, посмотреть его в маркетплейс.</p>
    </div>
<? endif; ?>

