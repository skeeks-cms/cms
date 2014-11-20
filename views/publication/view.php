<?php
use yii\helpers\Html;
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 14.10.2014
 * @since 1.0.0
 */

/* @var $this yii\web\View */
/* @var $model common\models\Publication */
/* @var $personal bool */

$game = "";//$model->getGame()->one();
?>

<? if ($game) : ?>
<div id="itemHeader">
    <a href="<?= Yii::$app->urlManager->createUrl(["game/view", "seo_page_name" => $game->seo_page_name]); ?>" id="itemPhoto">
        <img src="<?= $game->getMainImage() ?>" alt="">
    </a>
    <section id="itemInfo">
        <header id="itemTitle">
            <h1><?= $game->name ?></h1>
        </header>
        <div id="itemRating">
        </div>
    </section>

    <div id="itemFollow">
        <!--<a href="#" class="btn crimson like">Мне нравится</a>-->
        <? echo \frontend\widgets\Votes::widget([
            "model" => $game
        ])?>

        <? echo \frontend\widgets\Subscribe::widget([
            "model" => $game
        ])?>
    </div>

    <figure id="itemBg" style="background-image: url(<?= $game->getImageCover() ?>);">
        <img src="<?= $game->getImageCover() ?>" class="popup" alt="" title="">
        <div class="shadow"></div>
        <figcaption><?= $game->name ?></figcaption>
    </figure>
</div>
<? endif; ?>

<section id="contentBox">

    <main class="mainContent">
        <header>
            <h1><?= $model->name; ?></h1>
        </header>
        <time datetime="<?= Yii::$app->formatter->asRelativeTime($model->created_at); ?>"><?= Yii::$app->formatter->asRelativeTime($model->created_at); ?></time>
        <p>
            <?= $model->description_full; ?>
        </p>

        <? if ($images = $model->getImages()) : ?>
            <div class="attach-images">
                <? foreach ($images as $imgSrc) : ?>
                    <a href="<?= $imgSrc; ?>" target="_blank">
                        <img src="<?= $imgSrc; ?>" alt="">
                    </a>
                <? endforeach; ?>
            </div>
        <? endif; ?>

        <? echo \frontend\widgets\Comments::widget([
            "model" => $model
        ]); ?>
    </main>



    <aside id="sidebar" class="eh" style="width:38%;">

        <div class="module">
            <div class="ub">
                <a href="<?= $model->getCreatedBy()->one()->getPageUrl(); ?>" class="uph"><img src="<?= $model->getCreatedBy()->one()->getMainImage(); ?>" alt="<?= $model->getCreatedBy()->one()->getDisplayName(); ?>"></a>
                <h5>Автор публикации</h5>
                <a href="<?= $model->getCreatedBy()->one()->getPageUrl(); ?>" class="un"><?= $model->getCreatedBy()->one()->getDisplayName(); ?><span class="online">•</span></a>
            </div>

            <? if ($user = \skeeks\cms\App::user()) : ?>
                <? if ($model->getCreatedBy()->one()->getId() != $user->getId()) : ?>
                    <? echo \frontend\widgets\Subscribe::widget([
                        "model" => $model->getCreatedBy()->one()
                    ])?>
                <? endif; ?>
            <? else : ?>
                <? echo \frontend\widgets\Subscribe::widget([
                    "model" => $model->getCreatedBy()->one()
                ])?>
                <!--<a href="#" class="btn">Подписаться на автора</a>
                <a href="#" class="btn1 gray">Вы подписаны на автора</a>-->
            <? endif; ?>

        </div>

        <? if ($game) : ?>
        <!-- Game info -->
        <div class="module module1">
            <h3>Информация об игре</h3>
            <div class="module-p"><?= $game->description_short; ?></div>
            <ul class="plist">
                <li>
                    <h5 class="plist-h5">Разработчик</h5>
                    <a href="#" class="plist-a"><?= $game->developer; ?></a>
                </li>
                <li>
                    <h5 class="plist-h5">Жанры</h5>
                    <? if ($find = $game->getGenres()): ?>
                        <? foreach($find->all() as $genre) : ?>
                            <?= Html::a($genre->name, Yii::$app->urlManager->createUrl(["game-genre/view", "seo_page_name" => $genre->seo_page_name])); ?>
                        <? endforeach; ?>
                    <? endif; ?>
                </li>

                <li>
                    <h5 class="plist-h5">Игровые платформы</h5>
                    <? if ($find = $game->getPlatforms()): ?>
                        <? foreach($find->all() as $genre) : ?>
                            <?= Html::a($genre->name, Yii::$app->urlManager->createUrl(["game-platform/view", "seo_page_name" => $genre->seo_page_name])); ?>
                        <? endforeach; ?>
                    <? endif; ?>
                </li>

                <li>
                    <h5 class="plist-h5">Издатели</h5>
                    <? if ($find = $game->getPublishers()): ?>
                        <? foreach($find->all() as $genre) : ?>
                            <?= Html::a($genre->name, Yii::$app->urlManager->createUrl(["game-company/view", "seo_page_name" => $genre->seo_page_name])); ?>
                        <? endforeach; ?>
                    <? endif; ?>
                </li>

                <li>
                    <h5 class="plist-h5">Разработчики</h5>
                    <? if ($find = $game->getDevelopers()): ?>
                        <? foreach($find->all() as $genre) : ?>
                            <?= Html::a($genre->name, Yii::$app->urlManager->createUrl(["game-company/view", "seo_page_name" => $genre->seo_page_name])); ?>
                        <? endforeach; ?>
                    <? endif; ?>
                </li>


            </ul>
            <a href="#" class="btn2 btn3">Больше информации</a>
        </div>

        <? endif; ?>



    </aside>

</section>
