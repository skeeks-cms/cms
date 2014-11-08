<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.11.2014
 * @since 1.0.0
 */
?>

<?= \yii\helpers\Html::a("Удалить временные файлы", \skeeks\cms\helpers\UrlHelper::construct('admin/clear/index')->enableAdmin(), [
    'class'             => 'btn btn-primary',
    'data-method'       => 'post'
]); ?>
<hr />
<? foreach ($clearDirs as $dataDir) : ?>
    <?php
    /**
     * @var \skeeks\sx\Dir $dir
     */
        $dir = \yii\helpers\ArrayHelper::getValue($dataDir, 'dir');
    ?>
    <div class="row-fluid">
        <b><?= \yii\helpers\ArrayHelper::getValue($dataDir, 'label'); ?></b>: <?= $dir->getSize()->formatedShortSize(); ?><br />
        <?= $dir->getPath(); ?>
    </div>
    <hr />
<? endforeach; ?>