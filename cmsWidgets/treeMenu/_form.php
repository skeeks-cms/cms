<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.05.2015
 */
/* @var $this yii\web\View */
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
?>
<?php $form = ActiveForm::begin(); ?>
    <?= $form->fieldSet('Отображение'); ?>
        <?= $form->field($model, 'viewFile')->textInput(); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Фильтрация'); ?>
        <?= $form->fieldRadioListBoolean($model, 'enabledCurrentTree', \Yii::$app->cms->booleanFormat())
            ->hint('Если будет выбрано "да", то в выборку разделов добавиться условие фильтрации, разделов сайта, где вызван компонент'); ?>
        <?= $form->fieldSelect($model, 'active', \Yii::$app->cms->booleanFormat()); ?>
        <?= $form->fieldInputInt($model, 'level'); ?>
        <?= $form->fieldSelectMulti($model, 'site_codes', \yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\CmsSite::find()->active()->all(),
            'code',
            'name'
        )); ?>
        <?= $form->field($model, 'treePid')->widget(
            \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
            [
                'mode' => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_SINGLE,
                'attributeSingle' => 'treePid'
            ]
        ); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Сортировка'); ?>
        <?= $form->fieldSelect($model, 'orderBy', (new \skeeks\cms\models\Tree())->attributeLabels()); ?>
        <?= $form->fieldSelect($model, 'order', [
            SORT_ASC    => "ASC (от меньшего к большему)",
            SORT_DESC   => "DESC (от большего к меньшему)",
        ]); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Дополнительно'); ?>
        <?= $form->field($model, 'label')->textInput(); ?>
    <?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Настройки кэширования'); ?>
        <?= $form->fieldRadioListBoolean($model, 'enabledRunCache', \Yii::$app->cms->booleanFormat()); ?>
        <?= $form->fieldInputInt($model, 'runCacheDuration'); ?>
    <?= $form->fieldSetEnd(); ?>



<?= $form->buttonsStandart($model) ?>
<?php ActiveForm::end(); ?>