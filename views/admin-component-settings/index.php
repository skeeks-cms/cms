<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $loadedComponents
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */

?>

<? if ($component && $component->existsConfigFormFile()) : ?>
    <p>
        <?/* if ($component->getDefaultSettings()) : */?><!--
            <button type="submit" class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-remove"></i> сбросить настройки по умолчанию</button>
        --><?/* endif; */?>
    </p>
    <?= $component->renderConfigForm(); ?>
<? else: ?>
    <p>Нет доступных настроек</p>
<? endif; ?>


<?/* \skeeks\cms\modules\admin\widgets\Pjax::end(); */?>
