<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 24.03.2018
 */

namespace skeeks\cms\widgets;

use skeeks\cms\widgets\assets\DualSelectAsset;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\InputWidget;
/**
 * Class SortableDualList
 * @package skeeks\cms\widgets
 */
class SortSelect extends InputWidget
{
    /**
     * @var array
     */
    public $wrapperOptions = [];

    /**
     * @var array
     */
    public $items = [];

    /**
     * @var array
     */
    public $jsOptions = [];


    public $itemOptions = [
        'tag' => 'li'
    ];


    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->hasModel()) {
            throw new InvalidConfigException('!!!');
        }

        Html::addCssClass($this->options, 'sx-select-element');

        if (!ArrayHelper::getValue($this->options, 'id')) {
            $this->options['id'] = Html::getInputId($model, $attribute);
            $this->jsOptions['element_id'] = $this->options['id'];
        }

        $this->wrapperOptions['id'] = $this->id;
        $this->jsOptions['id'] = $this->id;

        $this->options['multiple'] = true;

        $items = (array) $this->model->{$this->attribute};
        $selectedItems = [];
        if ($items) {
            foreach ($items as $value)
            {
                $selectedItems[$value] = $value;
            }
        }

        $element = Html::activeListBox($this->model, $this->attribute, (array) $selectedItems, $this->options);

        echo $this->render('sort-select', [
            'element' => $element,
        ]);
    }

}