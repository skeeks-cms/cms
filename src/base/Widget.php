<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */

namespace skeeks\cms\base;

use skeeks\cms\components\Cms;
use skeeks\cms\traits\TWidget;
use yii\base\InvalidCallException;
use yii\base\ViewContextInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class Widget
 * @package skeeks\cms\base
 */
abstract class Widget extends Component implements ViewContextInterface
{
    //Умеет все что умеет \yii\base\Widget
    use TWidget;

    /**
     * @var array
     */
    public $contextData = [];

    /**
     * @var string
     */
    protected $_token;

    /**
     * Признак срабатывания функции static self::begin()
     * @var bool
     */
    protected $_isBegin = false;
    /**
     * @param string $namespace Unique code, which is attached to the settings in the database
     * @param array  $config Standard widget settings
     *
     * @return static
     */
    public static function beginWidget($namespace, $config = [])
    {
        $config = ArrayHelper::merge(['namespace' => $namespace], $config);
        return static::begin($config);
    }
    /**
     * Begins a widget.
     * This method creates an instance of the calling class. It will apply the configuration
     * to the created instance. A matching [[end()]] call should be called later.
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return static the newly created widget instance
     */
    public static function begin($config = [])
    {
        $config['class'] = get_called_class();
        /* @var $widget Widget */
        $widget = \Yii::createObject($config);
        static::$stack[] = $widget;
        //Если включена дебаг мод, будет напечатан первый тег
        echo $widget->_begin();

        return $widget;
    }
    /**
     * Если включена дебаг мод, будет напечатан первый тег
     * @return string
     */
    public function _begin()
    {
        //Запускается 1 раз
        if ($this->_isBegin === true) {
            return "";
        }
        $this->_isBegin = true;

        \Yii::$app->cmsToolbar->initEnabled();
        if (\Yii::$app->cmsToolbar->editWidgets == Cms::BOOL_Y && \Yii::$app->cmsToolbar->enabled) {
            $id = 'sx-infoblock-'.$this->id;

            return Html::beginTag('div',
                [
                    'class' => 'skeeks-cms-toolbar-edit-view-block',
                    'id'    => $id,
                    'title' => \Yii::t('skeeks/cms', "Double-click on the block will open the settings manager"),
                    'data'  =>
                        [
                            'id'         => $this->id,
                            'config-url' => $this->getCallableEditUrl(),
                        ],
                ]);
        }

        return "";
    }
    /**
     * Ends a widget.
     * Note that the rendering result of the widget is directly echoed out.
     * @return static the widget instance that is ended.
     * @throws InvalidCallException if [[begin()]] and [[end()]] calls are not properly nested
     */
    public static function end()
    {
        if (!empty(static::$stack)) {
            $widget = array_pop(static::$stack);
            if (get_class($widget) === get_called_class()) {
                echo $widget->run();
                //В режиме редактирования, будет добавлен закрывающий тег + зарегистрированные данные для js
                echo $widget->_end();
                return $widget;
            } else {
                throw new InvalidCallException(\Yii::t('skeeks/cms', '"Expecting end() of {widget}, found {class}',
                    ['widget' => get_class($widget), 'class' => get_called_class()]));
            }
        } else {
            throw new InvalidCallException(\Yii::t('skeeks/cms',
                "Unexpected {class}::end() call. A matching begin() is not found.", ['class' => get_called_class()]));
        }
    }

    public function init()
    {
        $this->_token = \Yii::t('skeeks/cms', 'Widget').': '.$this->id;
        \Yii::beginProfile("Cms Widget: ".$this->_token);
        parent::init();
        \Yii::endProfile("Cms Widget: ".$this->_token);
    }
    /**
     * @return string
     */
    public function run()
    {
        if (YII_ENV == 'prod') {
            try {
                \Yii::beginProfile("Run: ".$this->_token);
                $content = $this->_run();
                \Yii::endProfile("Run: ".$this->_token);
            } catch (\Exception $e) {
                $content = \Yii::t('skeeks/cms', 'Error widget {class}',
                        ['class' => $this->className()])." (".$this->descriptor->name."): ".$e->getMessage();
            }
        } else {
            \Yii::beginProfile("Run: ".$this->_token);
            $content = $this->_run();
            \Yii::endProfile("Run: ".$this->_token);
        }

        if ($this->_isBegin) {
            $result = $content;
        } else {
            $result = $this->_begin();
            $result .= $content;
            $result .= $this->_end();
        }

        return $result;
    }
    /**
     * @return string
     */
    protected function _run()
    {
        return '';
    }
    /**
     * В режиме редактирования, будет добавлен закрывающий тег + зарегистрированные данные для js
     * @return string
     */
    public function _end()
    {
        $result = "";

        if (\Yii::$app->cmsToolbar->editWidgets == Cms::BOOL_Y && \Yii::$app->cmsToolbar->enabled) {
            $id = 'sx-infoblock-'.$this->id;

            $this->view->registerJs(<<<JS
new sx.classes.toolbar.EditViewBlock({'id' : '{$id}'});
JS
            );
            $callableData = $this->callableData;

            $callableDataInput = Html::textarea('callableData', base64_encode(serialize($callableData)), [
                'id'    => $this->callableId,
                'style' => 'display: none;',
            ]);

            $result = $callableDataInput;
            $result .= Html::endTag('div');
        }

        return $result;
    }
}