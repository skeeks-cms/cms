<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\widgets\base\hasModelsSmart\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->fieldSet('Ключевые слова'); ?>

    <?= $form->field($model, 'enableKeywordsGenerator')->widget(
        \skeeks\widget\chosen\Chosen::className(),
        [
            'items' => [
                1 => 'Включена',
                0 => 'Выключена'
            ]
        ]
    )->hint('Если на странице не заданы ключевые слова, то они будут сгенерированны для нее, по определенным правилам автоматически.'); ?>

    <?= $form->field($model, 'minKeywordLenth')->textInput()->hint('Минимальная длина ключевого слова, которое попадает в список ключевых (при автоматической генерации)'); ?>
    <?= $form->field($model, 'maxKeywordsLength')->textInput()->hint('Максимальная длинна строки ключевых слов (при автоматической генерации)'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
