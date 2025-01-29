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
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\Module;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\widgets\formInputs\selectTree\assets\SelectTreeInputWidgetAsset;
use skeeks\cms\widgets\Pjax;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

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
    /**
     * @var array
     */
    public $wrapperOptions = [];
    public $defaultOptions = [
        'autocomplete' => 'off'
    ];

    /**
     * @var bool
     */
    public $multiple = false;

    public function init()
    {
        $this->wrapperOptions['id'] = $this->id . "-wrapper";

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

        echo $this->render('daterange', [
            'widget' => $this,
            'element' => $element
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
