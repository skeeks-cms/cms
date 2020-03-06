<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * @property string $wrapperId
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class InputWidget extends \yii\widgets\InputWidget
{
    /**
     * @var string
     */
    static public $autoIdPrefix = "InputWidget";

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var array
     */
    public $defaultOptions = [
        'type'  => 'number',
        'class' => 'form-control',
    ];

    /**
     * @var array
     */
    public $wrapperOptions = [];

    /**
     * @var array
     */
    public $clientOptions = [];

    /**
     * @var
     */
    public $viewFile;

    /**
     * @var bool
     */
    public $dynamicReload = false;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!$this->viewFile) {
            throw new InvalidConfigException("Need view file");
        }

        $this->wrapperOptions['id'] = $this->wrapperId;
        $this->clientOptions['id'] = $this->wrapperId;

        if ($this->dynamicReload) {
            $this->defaultOptions[\skeeks\cms\helpers\RequestResponse::DYNAMIC_RELOAD_FIELD_ELEMENT] = "true";
        }

        $r = new \ReflectionClass(static::class);

        Html::addCssClass($this->wrapperOptions, "sx-".Inflector::camel2id($r->getShortName()));
    }

    /**
     * @return string
     */
    public function getWrapperId()
    {
        return $this->id."-widget";
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $element = '';
        $options = ArrayHelper::merge($this->defaultOptions, $this->options);

        Html::addCssClass($options, 'sx-value-element');
        
        if ($this->hasModel()) {

            $element = Html::activeTextInput($this->model, $this->attribute, $options);

        } else {
            $element = Html::textInput($this->name, $this->value, $options);
        }


        return $this->render($this->viewFile, [
            'element' => $element,
        ]);
    }
}