<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

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
<?= $form->fieldSet('Индексация'); ?>
    <?= $form->field($model, 'robotsContent')->textarea(['rows' => 7])->hint('Это значение будет добавлено в автоматически сгенерированный файл robots.txt, в том случае если его не будет физически создано на сервере.'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Коды счетчиков'); ?>
    <?= $form->field($model, 'countersContent')->textarea(['rows' => 20]); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Адресация страниц'); ?>
    <?= $form->field($model, 'useLastDelimetrTree')->radioList(\Yii::$app->formatter->booleanFormat)->hint(''); ?>
    <?= $form->field($model, 'useLastDelimetrContentElements')->radioList(\Yii::$app->formatter->booleanFormat)->hint(''); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
