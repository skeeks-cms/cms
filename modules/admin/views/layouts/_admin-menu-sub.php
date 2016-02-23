<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.02.2016
 *
 * @var $this \yii\web\View
 * @var $subAdminMenuItems []
 */
?>
<? if ($subAdminMenuItems) : ?>
    <ul class="nav nav-sidebar">
    <? foreach ($subAdminMenuItems as $subAdminMenuItem) : ?>
        <? if ($subAdminMenuItem->isAllowShow()) : ?>
            <li <?= $subAdminMenuItem->isActive() ? 'class="active opened sx-start-opened"' : '' ?>>
                <a href="<?= $subAdminMenuItem->getUrl() ? $subAdminMenuItem->getUrl() : "#" ?>" title="<?= $subAdminMenuItem->label; ?>">
                    <span class="sx-icon">
                        <img src="<?= $subAdminMenuItem->getImgUrl(); ?>" />
                    </span>
                    <span class="txt"><?= $subAdminMenuItem->label; ?></span>
                    <? if ($subAdminMenuItem->items) : ?>
                        <span class="caret"></span>
                    <? endif; ?>
                </a>

                <?= $this->render('_admin-menu-sub', [
                    'subAdminMenuItems' => $subAdminMenuItem->items
                ]); ?>

            </li>
        <? endif; ?>
    <? endforeach; ?>
    </ul>
<? endif; ?>
