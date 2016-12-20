<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */
namespace skeeks\cms\base;

use skeeks\cms\components\Cms;
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
        $this->_token = \Yii::t('skeeks/cms','Widget').': ' . $this->id;

        $this->defaultAttributes = $this->attributes;

        \Yii::beginProfile("Init: " . $this->_token);
            $this->initSettings();
        \Yii::endProfile("Init: " . $this->_token);


        \Yii::$app->cmsToolbar->initEnabled();
        if (\Yii::$app->cmsToolbar->editWidgets == Cms::BOOL_Y && \Yii::$app->cmsToolbar->enabled)
        {
            $id = 'sx-infoblock-' . $this->id;

            echo Html::beginTag('div',
            [
                'class'     => 'skeeks-cms-toolbar-edit-view-block',
                'id'        => $id,
                'title'     => \Yii::t('skeeks/cms',"Double-click on the block will open the settings manager"),
                'data'      =>
                [
                    'id' => $this->id,
                    'config-url' => $this->getCallableEditUrl()
                ]
            ]);
        }
    }

    /**
     * @return string
     */
    public function run()
    {
        if (YII_ENV == 'prod')
        {
            try
            {
                \Yii::beginProfile("Run: " . $this->_token);
                    $content = $this->_run();
                \Yii::endProfile("Run: " . $this->_token);
            }
            catch (\Exception $e)
            {
                $content = \Yii::t('skeeks/cms','Error widget {class}',['class' => $this->className()]). " (" . $this->descriptor->name . "): " . $e->getMessage();
            }
        } else
        {
            \Yii::beginProfile("Run: " . $this->_token);
                $content = $this->_run();
            \Yii::endProfile("Run: " . $this->_token);
        }

        echo $content;

        if (\Yii::$app->cmsToolbar->editWidgets == Cms::BOOL_Y && \Yii::$app->cmsToolbar->enabled)
        {
            $id = 'sx-infoblock-' . $this->id;

            $this->view->registerJs(<<<JS
new sx.classes.toolbar.EditViewBlock({'id' : '{$id}'});
JS
);
            $callableData = $this->callableData;

            $callableDataInput = Html::textarea('callableData', base64_encode(serialize($callableData)), [
                'id'    => $this->callableId,
                'style' => 'display: none;'
            ]);

            echo $callableDataInput;
            echo Html::endTag('div');
        }
    }

    /**
     * @return string
     */
    protected function _run()
    {
        return '';
    }
}