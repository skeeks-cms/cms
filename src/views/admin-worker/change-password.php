<?php
/**
 * @var $this yii\web\View
 * @var $controller \skeeks\cms\backend\controllers\BackendModelController
 * @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm
 *
 */
$controller = $this->context;
$action = $controller->action;
\skeeks\cms\widgets\assets\CmsProfileAsset::register($this);
?>
<? $form = \skeeks\cms\backend\widgets\ActiveFormBackend::begin(); ?>
<div class="sx-password-control">
    <button type="button" class="sx-password-toggle sx-icon-action" data-sx-password-toggle
            aria-label="Показать пароль" aria-pressed="false">
        <i class="far fa-eye"></i>
    </button>
    <?= $form->field($dm, 'password')->passwordInput([
        'id'                     => 'sx-pass',
        'data-sx-password-input' => true,
    ]) ?>
    <div class="form-group">
        <a href="#" class="sx-password-generate" data-sx-password-generate
           data-sx-password-length="8">Сгенерировать пароль</a>
    </div>
</div>


<?php echo $form->errorSummary([$dm]); ?>
<?= $form->buttonsStandart($dm, ['save']); ?>


<? if ($is_saved) : ?>
    <?php
    $submitBtn = \Yii::$app->request->post('submit-btn');
    $this->registerJs(<<<JS
    sx.Window.openerWidgetTriggerEvent('model-update', {
        'submitBtn' : '{$submitBtn}'
    });
JS
    ); ?>
<? endif; ?>

<? $form::end(); ?>


