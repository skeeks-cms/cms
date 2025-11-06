<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */
/* @var $this yii\web\View */
/* @var $jsConfig [] */
/* @var $widget \skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget */

$options = $widget->clientOptions;
$clientOptions = \yii\helpers\Json::encode($options);
?>
<?php echo \yii\helpers\Html::beginTag("div", $widget->wrapperOptions) ?>
<?php echo $element; ?>
<?php echo \yii\helpers\Html::endTag("div"); ?>
<?

$jsDaterangeConfig = \yii\helpers\Json::encode($jsConfig);
$this->registerJs(<<<JS
$(function() {
    
    var jsDateRange = {$clientOptions};
    var wrapperId = jsDateRange['wrapperid'];
    
    var jInputWrapper = $("#" + wrapperId);
    var jInput = $("input", jInputWrapper);
    
    
  jInput.daterangepicker({$jsDaterangeConfig});

  jInput.on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY')).trigger("change");
  });

  jInput.on('cancel.daterangepicker', function(ev, picker) {
      $(this).val('').trigger("change");
  });

});
JS
)
?>
