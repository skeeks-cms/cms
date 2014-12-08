<?php
/**
 * kuhni
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */

/**
 * //TODO: menu-top-contacts - add to last element
 *
 * @var \skeeks\cms\models\Tree[] $models
 * @var \skeeks\cms\widgets\treeChildrens\TreeChildrens $widget
 */
?>

<? if ($models) : ?>
    <ul id="top-menu" class="wrapper-inner">
    <? $count = 0; ?>
    <? foreach ($models as $count => $model) : ?>
        <?
            $count ++;
            $active = '';
        ?>
        <? if (\Yii::$app->request->getAbsoluteUrl() == $model->getPageUrl())
        {
            $active = 'active';
        }?>
        <li id="menu-top-catalog" class='<?= $active ?><?= $count == 1 ? ' first' : ''; ?>'>
            <? if ($active) : ?>
                <span><?= $model->name; ?></span>
            <? else : ?>
                <a href="<?= $model->getPageUrl(); ?>" title="<?= $model->name; ?>"><?= $model->name; ?></a>
            <? endif; ?>

        </li>
    <? endforeach; ?>
    </ul>
<? endif;?>