<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */

namespace skeeks\cms\widgets;
use skeeks\cms\base\Widget;
use skeeks\cms\helpers\UrlHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class Infoblock
 * @package skeeks\cms\widgets
 */
class Infoblock extends Widget
{
    /**
     * @var int|string
     */
    public $id              = null;


    /**
     * @var null соль используется для получения уникального id для базы данных
     */
    public $sold            = null;

    /**
     * @var string название
     */
    public $name            = '';

    /**
     * @var string описание
     */
    public $description     = '';


    /**
     * Делать новый запрос в базу обязательно, или использовать сохраненное ранее значение
     * @var bool
     */
    public $refetch = false;

    /**
     * @var array виджет который отработает по умолчанию
     * Задается обычно для yii, название класса и массив настроек
     */
    public $widget = [];



    /**
     * @var array
     */
    static public $regsteredBlocks = [];

    /**
     * @param $code
     * @return bool|string
     */
    public function getRegistered($code)
    {
        if (isset(self::$regsteredBlocks[$code]))
        {
            return self::$regsteredBlocks[$code];
        } else
        {
            return false;
        }
    }

    public function init()
    {
        parent::init();

        if (!$this->id)
        {
            if (isset($this->widget['class']))
            {
                $this->id = md5($this->sold . $this->widget['class']);
            }
        }
    }

    /**
     * @return string
     */
    public function run()
    {
        $result = "";

        if (!$this->id)
        {
            return '';
        }

        $result = $this->getRegistered($this->id);

        if ($result === false || $this->refetch)
        {
            //Поиск конфига в базе данных
            if (is_string($this->id))
            {
                $modelInfoblock = \skeeks\cms\models\Infoblock::fetchByCode($this->id);
            } else if (is_int($this->id))
            {
                $modelInfoblock = \skeeks\cms\models\Infoblock::fetchById($this->id);
            }

            //В базе на эту тему ничего не найдено
            if (!$modelInfoblock)
            {
                if ($this->widget)
                {
                    $classWidget = ArrayHelper::getValue((array) $this->widget, 'class');
                    if (!$classWidget)
                    {
                        $result = 'Нет обязательного атрибута class widget';
                    }

                    if (!is_subclass_of($classWidget, Widget::className()))
                    {
                        $result = "{$classWidget} должен быть наследован от " . Widget::className();
                    }

                    $data = $this->widget;
                    unset($data['class']);

                    $result = $classWidget::widget((array) $data);
                }

            } else
            {
                //Правила показа
                if (!$modelInfoblock->isAllow())
                {
                    return $result;
                }

                //Данные виджета по умолчанию
                $defaultConfig          = [];
                $defaultWdigetClassName = '';
                if ($this->widget)
                {
                    $defaultWdigetClassName = ArrayHelper::getValue((array) $this->widget, 'class');
                    $defaultConfig          = (array) $this->widget;
                    if (isset($defaultConfig['class']))
                    {
                        unset($defaultConfig['class']);
                    }
                }

                $config = $modelInfoblock->getMultiConfig();
                if ($modelInfoblock->getWidgetClassName() == $defaultWdigetClassName)
                {
                    $config = ArrayHelper::merge($config, $defaultConfig);
                }

                $widget = $modelInfoblock->createWidget();
                $widget->setAttributes($config, $safeOnly);

                $result = $widget->run();
            }

            self::$regsteredBlocks[$this->id] = $result;
        }

        if (\Yii::$app->cmsToolbar->isEditMode())
        {
            return Html::tag('div', $result, [
                'class' => 'skeeks-cms-toolbar-edit-mode',
                'data' => [
                    'id' => $modelInfoblock->id,
                    'config-url' => UrlHelper::construct('cms/admin-infoblock/config', ['id' => $modelInfoblock->id])->enableAdmin()
                        ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                ]
            ]);
        }

        /*return Html::tag('div', $result, [
            'style' => 'border: 1px solid red;'
        ]);*/
        return $result;
    }
}