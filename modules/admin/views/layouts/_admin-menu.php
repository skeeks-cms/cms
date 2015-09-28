<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.09.2015
 */
?>

<? if ($items = \Yii::$app->adminMenu->items) : ?>
    <? foreach ($items as $adminMenuItem) : ?>
        <? if ($adminMenuItem->isAllowShow() && $adminMenuItem->items) : ?>
            <div class="sidebar-menu" id="<?= $adminMenuItem->code; ?>">
                <div class="sx-head" title="<?= $adminMenuItem->label; ?>">
                    <? if ($imgUrl = $adminMenuItem->getImgUrl()) : ?>
                        <span class="sx-icon">
                            <img src="<?= $imgUrl; ?>" />
                        </span>
                    <? else : ?>
                        <i class="icon icon-arrow-up" style=""></i>
                    <? endif; ?>
                    <?= $adminMenuItem->label; ?>
                </div>

                <? if ($subAdminMenuItems = $adminMenuItem->items) : ?>
                    <ul class="nav nav-sidebar">
                    <? foreach ($subAdminMenuItems as $subAdminMenuItem) : ?>
                        <? if ($subAdminMenuItem->isAllowShow()) : ?>
                            <li <?= $subAdminMenuItem->isActive() ? 'class="active opened"' : '' ?>>
                                <a href="<?= $subAdminMenuItem->getUrl() ? $subAdminMenuItem->getUrl() : "#" ?>" title="<?= $subAdminMenuItem->label; ?>" class="sx-test">
                                    <span class="sx-icon">
                                        <img src="<?= $subAdminMenuItem->getImgUrl(); ?>" />
                                    </span>
                                    <span class="txt"><?= $subAdminMenuItem->label; ?></span>
                                    <? if ($subAdminMenuItem->items) : ?>
                                        <span class="caret"></span>
                                    <? endif; ?>
                                </a>


                                    <? if ($sub3AdminMenuItems = $subAdminMenuItem->items) : ?>
                                        <ul class="nav nav-sidebar">
                                        <? foreach ($sub3AdminMenuItems as $sub3AdminMenuItem) : ?>
                                            <? if ($sub3AdminMenuItem->isAllowShow()) : ?>
                                                <li <?= $sub3AdminMenuItem->isActive() ? 'class="active opened"' : '' ?>>
                                                    <a href="<?= $sub3AdminMenuItem->getUrl() ? $sub3AdminMenuItem->getUrl() : "#" ?>" title="<?= $sub3AdminMenuItem->label; ?>" class="sx-test">
                                                        <span class="sx-icon">
                                                            <img src="<?= $sub3AdminMenuItem->getImgUrl(); ?>" />
                                                        </span>
                                                        <span class="txt"><?= $sub3AdminMenuItem->label; ?></span>
                                                        <? if ($sub3AdminMenuItem->items) : ?>
                                                            <span class="caret"></span>
                                                        <? endif; ?>
                                                    </a>


                                                    <? if ($sub4AdminMenuItems = $sub3AdminMenuItem->items) : ?>
                                                        <ul class="nav nav-sidebar">
                                                        <? foreach ($sub4AdminMenuItems as $sub4AdminMenuItem) : ?>
                                                            <? if ($sub4AdminMenuItem->isAllowShow()) : ?>
                                                                <li <?= $sub4AdminMenuItem->isActive() ? 'class="active opened"' : '' ?>>
                                                                    <a href="<?= $sub4AdminMenuItem->getUrl() ?>" title="<?= $sub4AdminMenuItem->label; ?>" class="sx-test">
                                                                        <span class="sx-icon">
                                                                            <img src="<?= $sub4AdminMenuItem->getImgUrl(); ?>" />
                                                                        </span>
                                                                        <span class="txt"><?= $sub4AdminMenuItem->label; ?></span>
                                                                    </a>
                                                                </li>
                                                            <? endif; ?>
                                                        <? endforeach; ?>
                                                        </ul>
                                                    <? endif; ?>


                                                </li>
                                            <? endif; ?>
                                        <? endforeach; ?>
                                        </ul>
                                    <? endif; ?>

                            </li>


                        <? endif; ?>
                    <? endforeach; ?>
                    </ul>
                <? endif; ?>
            </div>
        <? endif; ?>
    <? endforeach; ?>
<? endif; ?>