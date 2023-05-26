<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2017
 */
/* @var $this \yii\web\View */
/* @var \yii\web\User $model */
?>
<h1>Личные данные</h1>

<div class="row">
    <div class="col-12" style="max-width: 50rem;">

        <?php $form = \skeeks\cms\backend\widgets\ActiveFormAjaxBackend::begin(); ?>

        <?
        \skeeks\cms\admin\assets\JqueryMaskInputAsset::register($this);
        $id = \yii\helpers\Html::getInputId($model, 'phone');
        $this->registerJs(<<<JS
$("#{$id}").mask("+7 999 999-99-99");
JS
        );
        ?>

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
        <?= $form->field($model, 'phone') ?>
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
