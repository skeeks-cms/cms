<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;
?>
<div class="row">

    <div class="col-8 mx-auto">
        <div class="card g-brd-gray-light-v7 text-center g-pt-40 g-pt-60--md">
            <header class="g-mb-30">
                <div class="" style="margin: auto; width: 150px; height: 150px; overflow: hidden; border-radius: 50%;">
                    <img class="img-fluid g-mb-14" src="<?php echo $model->image ? $model->image->src : \skeeks\cms\helpers\Image::getCapSrc(); ?>" alt="Image description">
                </div>
                <h3 class="g-font-weight-300 g-font-size-22 g-color-black g-mb-2"><?php echo $model->shortDisplayName; ?></h3>
                <em class="g-font-style-normal g-font-weight-300 g-color-gray-dark-v6">@<?php echo $model->username; ?></em>
            </header>

            <section class="row no-gutters g-brd-top g-brd-gray-light-v4">
                <div class="col-6 g-py-10 g-py-25--md">
                    <a href="tel:<?php echo $model->phone; ?>" class="g-font-weight-300">
                        <i class="fas fa-phone"></i> <?php echo $model->phone; ?>
                        <?php if ($model->phone_is_approved) : ?>
                            <i class="fas fa-check" style="color: green; font-size: 10px;" data-toggle="tooltip" title="Телефон подтвержден"></i>
                        <?php endif; ?>

                    </a>
                </div>

                <div class="col-6 g-brd-left--md g-brd-gray-light-v4 g-py-10 g-py-25--md">
                    <a href="mailto:<?php echo $model->email; ?>" class="g-font-weight-300">
                        <i class="far fa-envelope"></i> <?php echo $model->email; ?>
                        <?php if ($model->phone_is_approved) : ?>
                            <i class="fas fa-check" style="color: green; font-size: 10px;" data-toggle="tooltip" title="Email подтвержден"></i>
                        <?php endif; ?>
                    </a>
                </div>
            </section>
        </div>
    </div>
    <div class="col-8 mx-auto">
        <table class="table table-bordered ">
            <tr>
                <td style="width: 50%">Зарегистрирован(a)</td>
                <td><span title="<?php echo \Yii::$app->formatter->asDatetime($model->created_at); ?>" data-toggle="tooltip"><?php echo \Yii::$app->formatter->asRelativeTime($model->created_at); ?></span></td>
            </tr>
            <tr>
                <td>Время авторизации</td>
                <td><span title="<?php echo \Yii::$app->formatter->asDatetime($model->logged_at); ?>" data-toggle="tooltip"><?php echo \Yii::$app->formatter->asRelativeTime($model->logged_at); ?></span></td>
            </tr>
            <tr>
                <td>Время последней активности</td>
                <td><span title="<?php echo \Yii::$app->formatter->asDatetime($model->last_activity_at); ?>" data-toggle="tooltip">
                        <?php echo \Yii::$app->formatter->asRelativeTime($model->last_activity_at); ?></span>
                </td>
            </tr>

            <?php /*if (\skeeks\cms\models\CmsSite::find()->count() == 1) : */?><!--
                <tr>
                    <td>Права доступа</td>
                    <td><?php /*if ($roles = \Yii::$app->authManager->getRolesByUser($model->id)) : */?>
                            <?/* foreach ($roles as $role) : */?>
                                <label class="u-label u-label-default g-rounded-20 g-mr-5"><?php /*echo $role->description; */?></label>
                            <?/* endforeach; */?>
                        <?php /*endif; */?>
                    </td>
                </tr>
            --><?php /*endif; */?>
        </table>
    </div>
</div>
