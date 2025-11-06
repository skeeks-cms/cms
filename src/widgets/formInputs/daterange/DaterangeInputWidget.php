<?php
/**
 * SelectTree
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 13.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\widgets\formInputs\daterange;

use skeeks\cms\Exception;
use skeeks\cms\modules\admin\Module;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 *
 *
 *  <?= $form->field($model, 'treeIds')->widget(
 * \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
 * [
 * 'multiple' => true
 * ]
 * ); ?>
 *
 *
 *
 * Class SelectTreeInputWidget
 *
 * @package skeeks\cms\widgets\formInputs\selectTree
 *
 * @see https://www.daterangepicker.com/
 * @see https://github.com/dangrossman/daterangepicker
 */
class DaterangeInputWidget extends InputWidget
{
    public static $autoIdPrefix = 'SelectTreeInputWidget';

    /**
     * @var array
     */
    public $clientOptions = [];

    public $jsConfigPrepend = [];
    public $jsConfigAppend = [];

    protected $_daterangepickerDefaultConfig = [];
    /**
     * @var array
     */
    public $wrapperOptions = [];
    public $defaultOptions = [
        'autocomplete' => 'off',
    ];

    /**
     * @var bool
     */
    public $multiple = false;

    public function init()
    {
        $this->_daterangepickerDefaultConfig = [
            "autoUpdateInput" => false,
            "locale"          => [
                "format"           => "DD.MM.YYYY",
                "separator"        => " - ",
                "applyLabel"       => "Применить",
                "cancelLabel"      => "Сбросить фильтр",
                "fromLabel"        => "от",
                "toLabel"          => "до",
                "customRangeLabel" => "Диапазон",
                "weekLabel"        => "W",
                "daysOfWeek"       => [
                    "Вс",
                    "Пн",
                    "Вт",
                    "Ср",
                    "Чт",
                    "Пт",
                    "Сб",
                ],
                "monthNames"       => [
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
                    "Декабрь",
                ],
                "firstDay"         => 1,
            ],
            "ranges" => [
                "Сегодня" => new JsExpression("[moment(), moment()]"),
                "Вчера" => new JsExpression("[moment().subtract(1, 'days'), moment().subtract(1, 'days')]"),
                "Последние 7 дней" => new JsExpression("[moment().subtract(6, 'days'), moment()]"),
                "Последние 30 дней" => new JsExpression("[moment().subtract(29, 'days'), moment()]"),
                "Этот месяц" => new JsExpression("[moment().startOf('month'), moment().endOf('month')]"),
                "Прошлый месяц" => new JsExpression("[moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]"),
                "Этот год" => new JsExpression("[moment().startOf('year'), moment().endOf('year')]"),
                "Прошлый год" => new JsExpression("[moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]"),
            ]
        ];
        $this->wrapperOptions['id'] = $this->id."-wrapper";

        $this->clientOptions['id'] = $this->id;
        $this->clientOptions['wrapperid'] = $this->wrapperOptions['id'];

        Html::addCssClass($this->wrapperOptions, "sx-daterange");

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->options = ArrayHelper::merge($this->defaultOptions, $this->options);

        if ($this->hasModel()) {

            if (!array_key_exists('id', $this->options)) {
                $this->clientOptions['inputId'] = Html::getInputId($this->model, $this->attribute);
            } else {
                $this->clientOptions['inputId'] = $this->options['id'];
            }

            $element = Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            //TODO: реализовать для работы без модели
            $element = Html::textInput($this->name, $this->value, $this->options);
        }

        $this->registerAssets();
        
        $jsConfig = $this->_daterangepickerDefaultConfig;
        if ($this->jsConfigPrepend) {
            $jsConfig = ArrayHelper::merge($this->jsConfigPrepend, $jsConfig);
        }

        if ($this->jsConfigAppend) {
            $jsConfig = ArrayHelper::merge($jsConfig, $this->jsConfigAppend);
        }
        
        echo $this->render('daterange', [
            'widget'  => $this,
            'element' => $element,
            'jsConfig' => $jsConfig,
        ]);
    }

    /**
     * @return $this
     */
    public function registerAssets()
    {
        DaterangeInputWidgetAsset::register($this->view);
        return $this;
    }


}
