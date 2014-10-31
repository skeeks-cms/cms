<?php
use yii\helpers\Html;

use yii\widgets\ActiveForm;
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
/* @var $model common\models\User */
/* @var $personal bool */

$this->title = 'Личный кабинет пользователя';
$this->params['breadcrumbs'][] = $this->title;
?>
<div id="itemHeader">
    <a href="#" id="itemPhoto">
        <img src="<?= $model->getMainImage(); ?>" alt="">
    </a>
    <section id="itemInfo">
        <header id="itemTitle">
            <h1><?= $model->getDisplayName(); ?></h1>
        </header>
        <div id="itemRating">
            <!-- Adm rating -->
            <div class="rating">
                <h4>«Ничто не истинно. И все дозволено»</h4>
            </div>

        </div>
    </section>

    <div id="itemFollow">
        <!--<a href="#" class="btn crimson like">Мне нравится</a>-->


        <? if (!$personal) : ?>
            <? echo \frontend\widgets\Subscribe::widget([
                "model" => $model
            ])?>
        <? else: ?>
            <a href="#" class="btn darkblue" onclick="return false;">Редактировать страницу</a>
            <?= Html::a("Выход", Yii::$app->urlManager->createUrl("site/logout"), [
                "class" => "btn",
                'data-method' => 'post'
            ]); ?>
        <? endif; ?>
    </div>


    <figure id="itemBg">
        <img src="<?= $model->getImageCover(); ?>" class="popup" alt="" title="">
        <figcaption>Assassin's Creed</figcaption>
    </figure>

</div><!-- /itemHeader -->


<section id="contentBox">
    <div id="main" class="eh">
        <div id="main" class="eh">




            <main class="mainContent1">
                <ul class="tags1">
                    <li><a href="#" class="tags-a">Все публикации</a></li>
                    <li><a href="#">Новости</a></li>
                    <li><a href="#">Фото</a></li>
                    <li><a href="#">Видео</a></li>
                    <li><a href="#">Отзывы</a></li>
                </ul>
            </main>

            <?php $form = ActiveForm::begin([
                "options" => ["class" => "post-comment"],
                "action" => Yii::$app->urlManager->createUrl(["publication/add"]),
            ]); ?>
                <div class="ub">
                    <a href="<?= \skeeks\cms\App::user() ? \skeeks\cms\App::user()->getPageUrl() : "#" ?>" class="uph-img">
                        <img src="<?= \skeeks\cms\App::user() ? \skeeks\cms\App::user()->getMainImage() : \Yii::$app->params["noimage"] ?>" alt="<?= \skeeks\cms\App::user() ? \skeeks\cms\App::user()->getDisplayName() : "" ?>">
                    </a>
                </div>
                <div class="comment-input">
                    <div class="tawrap">
                        <?= Html::input("hidden", "linked_to_model", $model->getRef()->getClassName()); ?>
                        <?= Html::input("hidden", "linked_to_value", $model->getRef()->getValue()); ?>
                        <textarea name="content" placeholder="Что у вас нового?"></textarea>
                    </div>
                    <input type="submit" value="Отправить">
                    <span class="comment-span"><a href="#">Прикрепить</a></span>
                </div>
            <?php $form = ActiveForm::end(); ?>

            <div class="wrapp-main">
                <? if ($publications = $model->getPublications()->orderBy("created_at DESC")->all()): ?>
                    <?= skeeks\cms\App::renderFrontend("widgets/publication/list.php", [
                        "publications" => $publications
                    ])?>
                <? endif; ?>

            </div>
        </div>
        <aside id="sidebar" class="eh" style="width:38%;">

            <!-- Game info -->
            <div class="module module1">
                <h3>Информация о пользователе</h3>

                <ul class="plist">
                    <li>
                        <h5 class="plist-h5">Имя</h5>
                        <?= $model->name; ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Логин</h5>
                        <?= $model->username; ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Добавил игры</h5>
                        <?= count($model->getGames()->all()); ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Добавил жанры</h5>
                        <?= count($model->getGameGenres()->all()); ?>
                    </li>


                    <li>
                        <h5 class="plist-h5">Добавил компании</h5>
                        <?= count($model->getGameCompanies()->all()); ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Добавил платформы</h5>
                        <?= count($model->getGameCompanies()->all()); ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Публикации</h5>
                        <?= count($model->getPublications()->all()); ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Комментарии</h5>
                        <?= count($model->getComments()->all()); ?>
                    </li>


                    <li>
                        <h5 class="plist-h5">Загрузил изображений</h5>
                        <?= count($model->getStorageFiles()->all()); ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Всего голосов</h5>
                        <?= count($model->getVotes()->all()); ?>
                    </li>

                    <li>
                        <h5 class="plist-h5">Всего подписок</h5>
                        <?= count($model->getSubscribes()->all()); ?>
                    </li>
                </ul>

                <? if ($personal) : ?>
                    <a href="#" class="btn2 btn3">Редактировать</a>
                <? endif; ?>
            </div>

            <? $subscribes = $model->getSubscribesModels(\common\models\User::className()); ?>

            <div class="module module1">
                <? if ($subscribes) : ?>
                    <? $subscribes = $subscribes->all(); ?>

                <h3 class="frend">Друзья
                    <a href="#">Все друзья</a>
                    <span><?= count($subscribes); ?></span>
                </h3>

                    <ul class="frend">
                        <? foreach($subscribes as $userFriend) : ?>
                            <li>
                                <a href="<?= $userFriend->getPageUrl(); ?>"><img src="<?= $userFriend->getMainImage(); ?>" alt=""></a>
                                <a href="<?= $userFriend->getPageUrl(); ?>"><?= $userFriend->getDisplayName(); ?></a>
                            </li>
                        <? endforeach; ?>
                    </ul>
                <? endif; ?>
            </div>


            <? $subscribes = $model->getSubscribesModels(\common\models\Game::className()); ?>
            <div class="module module1">
                <? if ($subscribes) : ?>
                    <? $subscribes = $subscribes->all(); ?>

                <h3 class="frend">Подписан на игры
                    <a href="#">Все игры</a>
                    <span><?= count($subscribes); ?></span>
                </h3>

                    <ul class="frend">
                        <? foreach($subscribes as $sub) : ?>
                            <li>
                                <a href="<?= $sub->getPageUrl(); ?>"><img src="<?= $sub->getMainImage(); ?>" alt=""></a>
                                <a href="<?= $sub->getPageUrl(); ?>"><?= $sub->name; ?></a>
                            </li>
                        <? endforeach; ?>
                    </ul>
                <? endif; ?>
            </div>

        </aside>

    </div>
</section>

<?= Html::a("Выход", Yii::$app->urlManager->createUrl("site/logout"), [
    "class" => "loginshow",
    'data-method' => 'post'
]); ?>

<?= yii\authclient\widgets\AuthChoice::widget([
     'baseAuthUrl' => ['site/auth']
]) ?>