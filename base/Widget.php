<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */
namespace skeeks\cms\base;

use skeeks\cms\exceptions\NotConnectedToDbException;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\traits\WidgetTrait;
use yii\base\ViewContextInterface;
use yii\db\Exception;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class Widget
 * @package skeeks\cms\base
 */
abstract class Widget extends Component implements ViewContextInterface
{
    //Умеет все что умеет \yii\base\Widget
    use WidgetTrait;

    /**
     * @var string
     */
    protected $_token;

    public function init()
    {
        $this->_token = 'Виджет: ' . $this->id;

        \Yii::beginProfile("Init: " . $this->_token);
            $this->initSettings();
        \Yii::endProfile("Init: " . $this->_token);
    }

    /**
     * @return string
     */
    public function run()
    {
        try
        {
            \Yii::beginProfile("Run: " . $this->_token);
                $content = $this->_run();
            \Yii::endProfile("Run: " . $this->_token);
        }
        catch (\Exception $e)
        {
            $content = "Ошибка в виджете " . $this->className() . " (" . $this->descriptor->name . "): " . $e->getMessage();
        }

        if (\Yii::$app->cmsToolbar->isEditMode() && \Yii::$app->cmsToolbar->enabled)
        {
            $pre = "";
            /*$pre = Html::tag('pre', Json::encode($this->attributes), [
                'style' => 'display: none;'
            ]);*/

            $id = 'sx-infoblock-' . $this->getId();

            $this->getView()->registerJs(<<<JS
new sx.classes.toolbar.Infoblock({'id' : '{$id}'});
JS
);
            return Html::tag('div', $pre . (string) $content,
            [
                'class' => 'skeeks-cms-toolbar-edit-mode',
                'id'    => $id,
                'data' =>
                [
                    'id' => $this->getId(),
                    'config-url' => $this->getEditUrl()
                ]
            ]);
        }

        return $content;
    }

    /**
     * @return string
     */
    protected function _run()
    {
        return '';
    }
}