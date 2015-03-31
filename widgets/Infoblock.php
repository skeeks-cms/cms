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
     * @var bool Запрет на смену виджета. То есть если в коде был вызван инфоблок и в него был передан виджет, то через базу данных нельзя изменить виджет, можно только изменить параметры.
     */
    public $protectedWidget         = false;

    /**
     * @var array массив названий параметров, которые нельзя менять в данном виджете через админку.
     */
    public $protectedWidgetParams   = [];

    /**
     * @var bool Включен по умолчанию, можно не удалять код из шаблона а просто его отключить.
     */
    public $enabled                  = true;


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

        if ($this->enabled === false)
        {
            return '';
        }

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
                $modelInfoblock = \skeeks\cms\models\Infoblock::getByCode($this->id);
            } else if (is_int($this->id))
            {
                $modelInfoblock = \skeeks\cms\models\Infoblock::getById($this->id);
            }

            //В базе на эту тему ничего не найдено
            if (!$modelInfoblock)
            {
                if ($classWidget = $this->getWidgetClassName())
                {
                    $result = $classWidget::widget($this->getWidgetParams());
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
                    $config = $this->getResultWidgetConfig($modelInfoblock);
                }

                $widget = $modelInfoblock->createWidget();
                $widget->setAttributes($config, $safeOnly);

                $result = $widget->run();
            }

            self::$regsteredBlocks[$this->id] = $result;
        }

        if (\Yii::$app->cmsToolbar->isEditMode())
        {
            if (!$modelInfoblock)
            {
                $modelInfoblock = new \skeeks\cms\models\Infoblock();
                $modelInfoblock->setAttributesByWidgetInfoblock($this);
                $modelInfoblock->auto_created = 1;
                if (!$modelInfoblock->save(true))
                {
                    return 'Ошибка сохраненеия данных в базу';
                }

                $modelInfoblock->setCurrentSite(null);
                $modelInfoblock->setCurrentLang(null);
                $modelInfoblock->setMultiConfig($this->getWidgetParams());
                $modelInfoblock->save(false);

            } else
            {
                $modelInfoblock->setAttributesByWidgetInfoblock($this);
                if (!$modelInfoblock->save(true))
                {
                    return 'Ошибка сохранения данных в базу';
                }

                $resultConfig = $this->getResultWidgetConfig($modelInfoblock);

                $modelInfoblock->setCurrentSite(null);
                $modelInfoblock->setCurrentLang(null);
                $modelInfoblock->setMultiConfig($resultConfig);
                $modelInfoblock->save(false);
            }


            return Html::tag('div', $result, [
                'class' => 'skeeks-cms-toolbar-edit-mode',
                'data' => [
                    'id' => $modelInfoblock->id,
                    'config-url' => UrlHelper::construct('cms/admin-infoblock/config', ['id' => $modelInfoblock->id])->enableAdmin()
                        ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                ]
            ]);
        }

        return $result;
    }


    /**
     * @param \skeeks\cms\models\Infoblock $modelInfoblock
     * @return array
     */
    public function getResultWidgetConfig(\skeeks\cms\models\Infoblock $modelInfoblock)
    {
        $configSaved        = $modelInfoblock->getMultiConfig();
        $configDefault      = $this->getWidgetParams();
        $configProtected    = [];
        foreach ((array) $modelInfoblock->protected_widget_params as $paramCode)
        {
            if (isset($configDefault[$paramCode]))
            {
                $configProtected[$paramCode] = $configDefault[$paramCode];
            }
        }

        return ArrayHelper::merge($configSaved, $configProtected);
    }
    /**
     * Название класса виджета
     * @return bool|string
     */
    public function getWidgetClassName()
    {
        if ($this->widget)
        {
            $classWidget = (string) ArrayHelper::getValue((array) $this->widget, 'class');
            if (!$classWidget)
            {
                return false;
            }

            if (!is_subclass_of($classWidget, Widget::className()))
            {
                return false;
            }

            return $classWidget;
        }

        return false;
    }

    /**
     * Параметры выиджета
     * @return array
     */
    public function getWidgetParams()
    {
        $result = [];

        if ($this->widget && $this->getWidgetClassName())
        {
            $data = $this->widget;
            unset($data['class']);

            $result = $data;
        }

        return $result;
    }
}