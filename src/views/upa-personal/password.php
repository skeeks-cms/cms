<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2017
 */
/* @var $this \yii\web\View */
/* @var \skeeks\cms\models\forms\PasswordChangeForm $model */
\skeeks\cms\widgets\assets\CmsProfileAsset::register($this);

?>
<h1><?php echo \Yii::$app->user->identity->password_hash ? "Смена пароля" : "Установка пароля"; ?></h1>
<div class="row">
    <div class="col-12 sx-profile-form">

        <?php if (!\Yii::$app->user->identity->password_hash && \Yii::$app->cms->pass_is_need_change) : ?>
            <?php $alert = \yii\bootstrap\Alert::begin([
                'closeButton' => false,
                'id' => "no-pass",
                'options'     => [
                    'class' => 'alert-danger',
                ],
            ]); ?>
            <b>Внимание!</b> <br/> Для продолжения работы, придумайте свой постоянный пароль, с которым вы будете входить в систему в дальнейшем.

            <?php $alert::end(); ?>
        <?php endif; ?>

        <?php $form = \skeeks\cms\backend\widgets\ActiveFormAjaxBackend::begin([
                'clientSuccess' => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
        if ($("#no-pass").length) {
            $("#no-pass").fadeOut();
        }
    }
JS
)
        ]); ?>

        <?php /*if (!\Yii::$app->user->identity->password_hash && \Yii::$app->cms->pass_is_need_change) : */ ?><!--
            <div class="form-group">
                <b>Внимание!</b> <br/>Придумайте свой постоянный пароль, с которым вы будете входить в систему в дальнейшем.
            </div>
        --><?php /*endif; */ ?>

        <div class="sx-password-control">
            <button type="button" class="sx-password-toggle sx-icon-action" data-sx-password-toggle
                    aria-label="Показать пароль" aria-pressed="false">
                <i class="far fa-eye"></i>
            </button>
            <?= $form->field($model, 'password')->passwordInput([
                'data-sx-password-input' => true,
            ]) ?>
            <div class="form-group">
                <a href="#" class="sx-password-generate" data-sx-password-generate
                   data-sx-password-length="8">Сгенерировать пароль</a>
            </div>
        </div>

        <?= $form->buttonsStandart($model) ?>
        <?php $form::end(); ?>
    </div>
</div>
