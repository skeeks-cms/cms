<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget */

$options = $widget->clientOptions;
$clientOptions = \yii\helpers\Json::encode($options);
?>
<?php echo \yii\helpers\Html::beginTag("div", $widget->wrapperOptions) ?>
    <?php echo $element; ?>
<?php echo \yii\helpers\Html::endTag("div"); ?>
<?
$this->registerJs(<<<JS
$(function() {
    
    var jsDateRange = {$clientOptions};
    var wrapperId = jsDateRange['wrapperid'];
    
    var jInputWrapper = $("#" + wrapperId);
    var jInput = $("input", jInputWrapper);
    
    console.log(jInput);
    
  jInput.daterangepicker({
      autoUpdateInput: false,
      "locale": {
        "format": "MM.DD.YYYY",
        "separator": " - ",
        "applyLabel": "Применить",
        "cancelLabel": "Сбросить фильтр",
        "fromLabel": "от",
        "toLabel": "до",
        "customRangeLabel": "Диапазон",
        "weekLabel": "W",
        "daysOfWeek": [
            "Вс",
            "Пн",
            "Вт",
            "Ср",
            "Чт",
            "Пт",
            "Сб"
        ],
        "monthNames": [
            "Январь",
            "Февраль",
            "Март",
            "Апрель",
            "Май",
            "Июнь",
            "Июль",
            "Август",
            "Сентябрь",
            "Октябрь",
            "Ноябрь",
            "Декабрь"
        ],
        "firstDay": 1
    },
    
        ranges: {
           'Сегодня': [moment(), moment()],
           'Вчера': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Последние 7 дней': [moment().subtract(6, 'days'), moment()],
           'Последние 30 дней': [moment().subtract(29, 'days'), moment()],
           'Этот месяц': [moment().startOf('month'), moment().endOf('month')],
           'Прошлый месяц': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
           'Этот год': [moment().startOf('year'), moment().endOf('year')],
           'Прошлый год': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
        }
  });

  jInput.on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
  });

  jInput.on('cancel.daterangepicker', function(ev, picker) {
      $(this).val('');
  });

});
JS
)
?>
