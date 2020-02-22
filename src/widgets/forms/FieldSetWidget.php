<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
namespace skeeks\cms\widgets\forms;

use skeeks\cms\widgets\assets\FieldSetAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class FieldSetWidget extends Widget {

    /**
     * @var string
     */
    public static $autoIdPrefix = 'field-set';

    /**
     * @var ActiveForm
     */
    public $activeForm = null;
    /**
     * @var
     */
    public $name;

    /**
     * @var array
     */
    public $options = [];
    /**
     * @var array
     */
    public $countentOptions = [];
    /**
     * @var array
     */
    public $headerOptions = [];

    /**
     * @var bool
     */
    public $isOpen = true;

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        parent::init();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        $openedFromRequest = \Yii::$app->request->get($this->activeForm->id);
        if ($openedFromRequest) {
            if (!$this->isOpen && in_array($this->id, $openedFromRequest)) {
                $this->isOpen = true;
            }
            
            if ($this->isOpen && !in_array($this->id, $openedFromRequest)) {
                $this->isOpen = false;
            }
        }

        self::registerAssets($this->activeForm);
        if (!$this->isOpen) {
            Html::addCssClass($this->options, " sx-field-set-hidden");
        }
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->id;
        }
        Html::addCssClass($this->options, "sx-form-fieldset");
        Html::addCssClass($this->countentOptions, "sx-form-fieldset-content");
        Html::addCssClass($this->headerOptions, "sx-form-fieldset-title");

        ob_start();
        ob_implicit_flush(false);
    }

    static public $registerdForms = [];
    
    static public function registerAssets(ActiveForm $activeForm)
    {
        if (isset(self::$registerdForms[$activeForm->id])) {
            return true;
        }
        self::$registerdForms[$activeForm->id] = true;
        FieldSetAsset::register(\Yii::$app->view);
        $jsData = Json::encode([
            'form-id' => $activeForm->id
        ]);
        \Yii::$app->view->registerJs(<<<JS
    new sx.classes.ActiveFormFieldSet({$jsData});
JS
        );
    }

    /**
     * Runs the widget.
     * This registers the necessary JavaScript code and renders the form open and close tags.
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching.
     */
    public function run()
    {
        /*if (!empty($this->_fields)) {
            throw new InvalidCallException('Each beginField() should have a matching endField() call.');
        }*/
        $content = ob_get_clean();

        $html = $this->_begin();
        $html .= $content;
        $html .= $this->_end();
        return $html;
    }

    /**
     * @var null
     */
    private $_beginTag = null;

    /**
     * @var null
     */
    private $_beginContentTag = null;
    /**
     * @return string
     */
    protected function _begin() {

        $tag = ArrayHelper::getValue($this->options, "tag", "div");
        ArrayHelper::removeValue($this->options, "tag");
        $this->_beginTag = $tag;
        $html = Html::beginTag($tag, $this->options);

        $tag = ArrayHelper::getValue($this->headerOptions, "tag", "div");
        ArrayHelper::removeValue($this->headerOptions, "tag");
        $html .= Html::tag($tag, $this->name, $this->headerOptions);

        $tag = ArrayHelper::getValue($this->countentOptions, "tag", "div");
        ArrayHelper::removeValue($this->countentOptions, "tag");
        $this->_beginContentTag = $tag;
        $html .= Html::beginTag($tag, $this->countentOptions);

        return $html;
    }

    /**
     * @return string
     */
    protected function _end() {
        $html = Html::endTag($this->_beginTag) . Html::endTag($this->_beginContentTag);

        return $html;
    }
}