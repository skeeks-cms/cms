<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2017
 */
/* @var $this \yii\web\View */
/* @var \yii\web\User $model */
\skeeks\cms\widgets\assets\CmsProfilePhoneAsset::register($this);
?>
<!--<h1>Личные данные</h1>-->

<div class="row">
    <div class="col-12 sx-profile-form">
<?php if (!$model->email || !$model->first_name || !$model->last_name
    //|| !$model->image
) : ?>
    <?php $alert = \yii\bootstrap\Alert::begin([
        'closeButton' => false,
        'id' => "no-info",
        'options' => [
            'class' => 'alert-danger',
        ],
    ]); ?>
    <p><b>Внимание!</b></p>
    <p>Для продолжения работы с системой управления сайта требуется указать ваши данные:</p>
    <ul>
        <li>Реальный email</li>
        <li>Фамилия</li>
        <li>Имя</li>
        <!--<li>Фото</li>-->
    </ul>

    <?php $alert::end(); ?>
<?php endif; ?>

        <?php $form = \skeeks\cms\backend\widgets\ActiveFormAjaxBackend::begin([
                'clientSuccess' => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
        if ($("#no-info").length) {
            $("#no-info").fadeOut();
        }
    }
JS
)
        ]); ?>




        <?php echo $form->field($model, "image_id")->widget(\skeeks\cms\widgets\AjaxFileUploadWidget::class, [
            //'view_file'   => '@skeeks/yii2/ajaxfileupload/widgets/views/default',
            'accept'   => 'image/*',
            'multiple' => false,
        ]); ?>

        <?= $form->field($model, 'gender')->widget(
            \skeeks\cms\widgets\Select::class,
            [
                'options' => [
                    'placeholder' => "Пол не указан..."
                ],
                'data' => [
                    'men'   => \Yii::t('skeeks/cms', 'Male'),
                    'women' => \Yii::t('skeeks/cms', 'Female'),
                ]
            ]
        ); ?>

        <?= $form->field($model, 'first_name') ?>
        <?= $form->field($model, 'last_name') ?>
        <?= $form->field($model, 'patronymic') ?>
        <?= $form->field($model, 'email') ?>
        <?= $form->field($model, 'phone')->textInput([
            'data-sx-phone-mask' => '+7 999 999-99-99',
        ]) ?>
        <?= $form->field($model, 'birthday_at')->widget(
            \skeeks\cms\backend\widgets\forms\DateControlInputWidget::class,
            [
                'type' => \skeeks\cms\backend\widgets\forms\DateControlInputWidget::FORMAT_DATE
            ]
        ); ?>
        <?= $form->buttonsStandart($model) ?>
        <?= $form->errorSummary([$model]) ?>
        <?php $form::end(); ?>
    </div>
</div>
