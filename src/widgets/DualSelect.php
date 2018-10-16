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
class DualSelect extends InputWidget
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


    /**
     * @var array|string
     */
    public $visibleLabel = [
        'tag' => 'h3',
        'body' => 'Visibles'
    ];

    /**
     * @var array|string
     */
    public $hiddenLabel = [
        'tag' => 'h3',
        'body' => 'Hiddens'
    ];

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

        echo $this->render('dual-select', [
            'element' => $element,
        ]);
    }

    /**
     * @param $value
     * @param $item
     */
    public function renderItem($value, $item)
    {
        $itemOptions = $this->itemOptions;
        $tag = ArrayHelper::getValue($itemOptions, 'tag', 'li');
        ArrayHelper::remove($itemOptions, 'tag');

        $itemOptions['data']['value'] = $value;

        echo Html::beginTag($tag, $itemOptions);
            echo $item;
        echo Html::endTag($tag);
    }

    /**
     * @param        $options
     * @param string $defaultTab
     * @return mixed
     */
    public function renderHtml($options, $defaultTab = 'h3')
    {
        if (is_string($options)) {
            return $this->renderHtml([
                'body' => $options
            ]);
        }

        $tag = ArrayHelper::getValue($options, 'tag', $defaultTab);
        ArrayHelper::remove($options, 'tag');

        $body = ArrayHelper::getValue($options, 'body');
        ArrayHelper::remove($options, 'body');

        echo Html::beginTag($tag, $options);
            echo $body;
        echo Html::endTag($tag);
    }
}