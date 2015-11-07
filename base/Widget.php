<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */
namespace skeeks\cms\base;

use skeeks\cms\components\Cms;
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
        $this->_token = \Yii::t('app','Widget').': ' . $this->id;

        $this->defaultAttributes = $this->attributes;

        \Yii::beginProfile("Init: " . $this->_token);
            $this->initSettings();
        \Yii::endProfile("Init: " . $this->_token);
    }


    /**
     * В ключ добавляется зависимость, какой режим используется
     * @return string
     */
    /*public function getCacheKey()
    {
        \Yii::$app->cmsToolbar->initEnabled();
        if (\Yii::$app->cmsToolbar->isEditMode() && \Yii::$app->cmsToolbar->enabled)
        {
            return implode([
                "edit-mode-true"
            ]) . parent::getCacheKey();
        } else
        {
            parent::getCacheKey();
        }
    }*/

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
                $content = \Yii::t('app','Error widget {class}',['class' => $this->className()]). " (" . $this->descriptor->name . "): " . $e->getMessage();
            }
        } else
        {
            \Yii::beginProfile("Run: " . $this->_token);
                $content = $this->_run();
            \Yii::endProfile("Run: " . $this->_token);
        }


        \Yii::$app->cmsToolbar->initEnabled();
        if (\Yii::$app->cmsToolbar->editWidgets == Cms::BOOL_Y && \Yii::$app->cmsToolbar->enabled)
        {
            $pre = "";
            /*$pre = Html::tag('pre', Json::encode($this->attributes), [
                'style' => 'display: none;'
            ]);*/

            $id = 'sx-infoblock-' . $this->getId();

            $this->getView()->registerJs(<<<JS
new sx.classes.toolbar.EditViewBlock({'id' : '{$id}'});
JS
);
            return Html::tag('div', $pre . (string) $content,
            [
                'class'     => 'skeeks-cms-toolbar-edit-view-block',
                'id'        => $id,
                'title'     => \Yii::t('app',"Double-click on the block will open the settings manager"),
                'data'      =>
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