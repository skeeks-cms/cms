<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 * @var $site \skeeks\cms\models\CmsSite
 */
/* @var $this yii\web\View */
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


    <h2>Настройки для сайта: <?= $site->name; ?> (<?= $site->code; ?>)</h2>
    <div class="sx-box sx-mb-10 sx-p-10">
        <? if ($settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponentSiteCode($component, $site->code)) : ?>
            <button type="submit" class="btn btn-danger btn-xs" onclick="sx.ComponentSettings.Remove.removeBySite('<?= $site->code; ?>'); return false;">
                <i class="glyphicon glyphicon-remove"></i> сбросить настройки для этого сайта
            </button>
            <small>Настройки для данного компонента сохранены в базу данных. Эта опция сотрет их из базы, но компонент, восстановит значения по умолчанию. Как их указал разработчик в коде.</small>
        <? else: ?>
            <small>Эти настройки еще не сохранялись в базу данных</small>
        <? endif; ?>
    </div>
    <?= $component->renderConfigForm(); ?>


<?= $this->render('_footer'); ?>
