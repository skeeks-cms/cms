<?php
/**
 * social
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.01.2015
 * @since 1.0.0
 */
?>
<h2>Социальные привязки</h2>
<? if ($comments) : ?>
    <h3>Комментарии:</h3>
    <?= $comments; ?>
<? endif; ?>
<? if ($votes) : ?>
    <h3>Голоса:</h3>
    <?= $votes; ?>
<? endif; ?>
<? if ($subscribes) : ?>
    <h3>Подписчики:</h3>
    <?= $subscribes; ?>
<? endif; ?>