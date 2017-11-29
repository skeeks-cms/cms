<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget */

$options = $widget->clientOptions;
$clientOptions = \yii\helpers\Json::encode($options);
?>
<div id="<?= $widget->id; ?>">
    <div class="sx-select-controll">
        <?php if ($widget->modelAttributeSaveType) : ?>
            <?= \yii\helpers\Html::activeRadioList($widget->model, $widget->modelAttributeSaveType,
                \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::editors()) ?>
        <?php else
            : ?>
            <?= \yii\helpers\Html::radioList(
                $widget->id . '-radio',
                $widget->defaultEditor,
                \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::editors()
            ) ?>
        <?php endif;
        ?>
    </div>
    <div class="sx-controll">
        <?= $textarea; ?>
    </div>
</div>

<?
//TODO: убрать в файл


$this->registerCss(<<<CSS
    .CodeMirror
    {
        height: 400px;
    }
CSS
);


$this->registerJs(<<<JS
(function(sx, $, _)
{
    new sx.classes.combotext.ComboTextInputWidget({$clientOptions});
})(sx, sx.$, sx._);
JS
)
?>
