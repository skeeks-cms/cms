<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


    <div class="sx-box sx-mb-10 sx-p-10">
        <p>Стереть все настройки из базы для данного компонента.</p>
        <? if ($settingsAllCount = \skeeks\cms\models\CmsComponentSettings::baseQuery($component)->count()) : ?>
            <p><b>Всего найдено:</b> <?= $settingsAllCount; ?></p>
            <button type="submit" class="btn btn-danger btn-xs" onclick="sx.ComponentSettings.Remove.removeAll(); return false;">
                <i class="glyphicon glyphicon-remove"></i> сбросить все настройки
            </button>
        <? else: ?>
            <small>В базе данных нет ни одной настройки для данного компонента.</small>
        <? endif; ?>
    </div>


<?= $this->render('_footer'); ?>



