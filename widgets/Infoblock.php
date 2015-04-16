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
use yii\helpers\Json;

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
     * @var string название
     */
    public $name            = '';

    /**
     * @var string описание
     */
    public $description     = '';

    /**
     * @var array массив названий параметров, которые нельзя менять в данном виджете через админку.
     */
    public $protectedWidgetParams   = false;

    /**
     * @var bool Включен по умолчанию, можно не удалять код из шаблона а просто его отключить.
     */
    public $enabled                  = true;


    /**
     * Пересобирать ли инфоблок заново, если у них совпадают ID
     * @var bool
     */
    public $refetch = true;

    /**
     * @var array виджет который отработает по умолчанию
     * Задается обычно для yii, название класса и массив настроек
     */
    public $widget = [];


    /**
     * @var \skeeks\cms\models\Infoblock
     */
    public $modelInfoblock = null;

    /**
     * @var string
     */
    public $result = null;

    /**
     * Вызываемые инфоблоки в этом сценарии
     * @var array
     */
    static public $regstered    = [];

    /**
     * @var int
     */
    static public $counter      = 0;




    /**
     * TODO: depricated 1.1.3 Если виджет передан в коде вызова инфоблока то тако инфоблоку уже с протектед виджетом.
     * @var bool Запрет на смену виджета. То есть если в коде был вызван инфоблок и в него был передан виджет, то через базу данных нельзя изменить виджет, можно только изменить параметры.
     */
    public $protectedWidget         = false;


    /**
     * @var string
     */
    protected $_token;
    /**
     * @return string
     */
    public function run()
    {
        //Валидация целостности данных инфоблока
        //Не указан id
        if (!$this->id)
        {
            \Yii::error('Не указан ID инфоблока: ' . Json::encode((array) $this->attributes));
            return "";
        }

        $this->_token = 'Инфоблок: ' . $this->id;

        \Yii::beginProfile($this->_token);
            $result = $this->_run();
        \Yii::endProfile($this->_token);

        return $result;

    }

    /**
     * @return string
     */
    protected function _run()
    {
        //Инфоблок выключен
        if ($this->enabled === false)
        {
            \Yii::info($this->_token . ' отключен в коде');
            return "";
        }

        //Блок с таким ID уже вызывался, и сказано что при повторном вызове его не нужно пересобирать
        if (isset(self::$regstered[$this->id]) && $this->refetch === false)
        {
            \Yii::info($this->_token . ' повторный вызов (результат взят из предыдущего вызова)');

            $block          = self::$regstered[$this->id];
            $this->result   = $block->result;

            return $this->_renderResult();
        } else if (isset(self::$regstered[$this->id]) && $this->refetch === true)
        {
            \Yii::warning($this->_token . ' повторный вызов (результат заново отрендерен)');
        }

        //Регистрация в стек вызовов
        self::$regstered[$this->id] = $this;
        //Запрос в базу на проверку есть ли данные по инфоблоку.
        $this->_initModel();

        //Проверка правил показа
        if (!$this->isAllow())
        {
            \Yii::info($this->_token . ' не разрешено показывать');
            return $this->_renderResult();
        }

        //Название класса виджета который необходимо запускать.
        if (!$widgetClassName = $this->getWidgetClassName())
        {
            \Yii::error($this->_token . ' не определен, не задан, или задан неправильно класс исполняемого виджета');
            return $this->_renderResult();
        }


        $widget = new $widgetClassName();
        $widget->setAttributes($this->getResultWidgetParams(), false);

        $this->result = $widget->run();

        return $this->_renderResult();
    }

    /**
     * Проверка разрешено ли выполнять инфоблок, для текущей страницы, для текущего юзера и т.д..
     * @return bool
     */
    public function isAllow()
    {
        //Правила показа
        /*if (!$this->modelInfoblock->isAllow())
        {
            return $result;
        }*/
        return true;
    }

    /**
     * Поиск и установка модели инфоблока.
     * @return $this
     */
    protected function _initModel()
    {
        $modelInfoblock = $this->fetchModel();
        if ($modelInfoblock)
        {
            $this->modelInfoblock = $modelInfoblock;
        }

        return $this;
    }
    /**
     *
     * Запрос на получение модели из базы данных для текущего инфоблока
     *
     * @return \skeeks\cms\models\Infoblock
     */
    public function fetchModel()
    {
        $modelInfoblock = null;

        \Yii::info($this->_token . ' запрос в базу за для проверки настроек');

        if (is_string($this->id))
        {
            $modelInfoblock = \skeeks\cms\models\Infoblock::getByCode($this->id);
        } else if (is_int($this->id))
        {
            $modelInfoblock = \skeeks\cms\models\Infoblock::getById($this->id);
        }

        return $modelInfoblock;
    }

    /**
     * Вывод результата
     * Регистрация его.
     *
     * @param string $result
     * @param \skeeks\cms\models\Infoblock|null $modelInfoblock
     * @return string
     */
    protected function _renderResult()
    {
        self::$counter = self::$counter + 1;

        if (\Yii::$app->cmsToolbar->isEditMode())
        {
            if (!$this->modelInfoblock)
            {
                $widgetParamsForSave = $this->getResultWidgetParams();

                $this->modelInfoblock = new \skeeks\cms\models\Infoblock();
                $this->modelInfoblock->setAttributesByWidgetInfoblock($this);
                $this->modelInfoblock->auto_created = 1;

                if (!$this->modelInfoblock->save(true))
                {
                    return 'Ошибка сохранения данных в базу';
                }


                $this->modelInfoblock->setCurrentSite(null);
                $this->modelInfoblock->setCurrentLang(null);
                $this->modelInfoblock->setMultiConfig($widgetParamsForSave);
                $this->modelInfoblock->save(false);

            } else
            {
                $this->modelInfoblock->setAttributesByWidgetInfoblock($this);
                if (!$this->modelInfoblock->save(true))
                {
                    return 'Ошибка сохранения данных в базу';
                }

                $this->modelInfoblock->setCurrentSite(null);
                $this->modelInfoblock->setCurrentLang(null);
                $this->modelInfoblock->setMultiConfig($this->getResultWidgetParams());
                $this->modelInfoblock->save(false);
            }


            $pre = Html::tag('pre', Json::encode($this->getResultWidgetParams()), [
                'style' => 'display: none;'
            ]);

            $id = 'sx-infoblock-' . self::$counter;

            $this->getView()->registerJs(<<<JS
new sx.classes.Infoblock({'id' : '{$id}'});
JS
);
            return Html::tag('div', $pre . (string) $this->result, [
                'class' => 'skeeks-cms-toolbar-edit-mode',
                'id'    => $id,
                'data' => [
                    'counter' => self::$counter,
                    'id' => $this->modelInfoblock->id,
                    'config-url' => UrlHelper::construct('cms/admin-infoblock/config', ['id' => $this->modelInfoblock->id])->enableAdmin()
                        ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                ]
            ]);
        }

        return (string) $this->result;
    }


    /**
     * Результирующие параметры для виджета, с учетом перекрытия закрытых параметров.
     * @return array
     */
    public function getResultWidgetParams()
    {
        //Есть ли модель инфоблока
        if ($this->modelInfoblock)
        {
            $configSaved        = (array) $this->modelInfoblock->getMultiConfig();

            if ($configProtected = $this->protectedWidgetParams())
            {
                return array_merge($configSaved, $configProtected);
            } else
            {
                return $configSaved;
            }
        } else
        {
            return $this->getCallingWidgetParams();
        }
    }

    /**
     * Параметры закрые от редактирования, а при мерже они важнее.
     *
     * @return array
     */
    public function protectedWidgetParams()
    {
        //Если параметров переданных в коде нет, то и закрытых параметров так же нет.
        if (!$callingParams = $this->getCallingWidgetParams())
        {
            return [];
        }

        //Можно указать массив ключей закрытых параметров
        if (is_array($this->protectedWidgetParams))
        {
            $configProtected = [];

            foreach ($this->protectedWidgetParams as $paramCode)
            {
                if (isset($callingParams[$paramCode]))
                {
                    $configProtected[$paramCode] = $callingParams[$paramCode];
                }
            }

            return $configProtected;

        } else if (is_bool($this->protectedWidgetParams)) //сказать что все параметры переданные в коде важные и нередактируемые через админку
        {
            if ($this->protectedWidgetParams === true)
            {
                return $callingParams;
            }
        }

        return [];
    }
    /**
     * Название класса виджета
     * @return bool|string
     */
    public function getWidgetClassName()
    {
        //Проверяем те данные которые указал разработчик в коде
        if ($this->widget)
        {
            $classWidget = (string) ArrayHelper::getValue((array) $this->widget, 'class');

            if (!$classWidget)
            {
                return false;
            }

            if (!class_exists($classWidget))
            {
                \Yii::info($this->_token . " $classWidget не найден класс исполняемого виджета ");
                return false;
            }

            if (!is_subclass_of($classWidget, Widget::className()))
            {
                \Yii::info($this->_token . " $classWidget класс исполняемого виджета должен быть наследован от " . Widget::className());
                return false;
            }

            return $classWidget;
        }

        //Проверяем то что указано в настройках в базе
        if ($this->modelInfoblock)
        {
            if ($classWidget = $this->modelInfoblock->getWidgetClassName())
            {
                if (!$classWidget)
                {
                    return false;
                }

                if (!class_exists($classWidget))
                {
                    \Yii::info($this->_token . " $classWidget не найден класс исполняемого виджета ");
                    return false;
                }

                if (!is_subclass_of($classWidget, Widget::className()))
                {
                    \Yii::info($this->_token . " $classWidget класс исполняемого виджета должен быть наследован от " . Widget::className());
                    return false;
                }

                return $classWidget;
            }
        }

        return false;
    }

    /**
     * Параметры выиджета заданные в коде при вызове инфоблока
     *
     * @return array
     */
    public function getCallingWidgetParams()
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