<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 07.11.2015
 */
/* @var $this yii\web\View */
/* @var $rootViewFile string */
/* @var $model \skeeks\cms\models\forms\ViewFileEditModel */
$this->registerCss(<<<CSS
.CodeMirror
{
    height: auto;
}
CSS
)
?>


<?php $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormStyled::begin([
    'useAjaxSubmit' => true,
    'usePjax' => false,
    'enableAjaxValidation' => false
]); ?>

<?= $form->field($model, 'source')->label($model->rootViewFile)->widget(
    \skeeks\widget\codemirror\CodemirrorWidget::className(),
    [
        'preset' => 'htmlmixed',
        'assets' =>
            [
                \skeeks\widget\codemirror\CodemirrorAsset::THEME_NIGHT
            ],
        'clientOptions' =>
            [
                'theme' => 'night'
            ],
        'options' => ['rows' => 40],
    ]
); ?>

<?= $form->buttonsStandart($model); ?>

<?php \skeeks\cms\modules\admin\widgets\form\ActiveFormStyled::end(); ?>
