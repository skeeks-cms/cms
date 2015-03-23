<?php
/**
 * Infoblock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 10.11.2014
 * @since 1.0.0
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
    public $id = null;

    /**
     * Дополнительные настройки
     * @var array
     */
    public $config = [];

    /**
     * Делать новый запрос в базу обязательно, или использовать сохраненное ранее значение
     * @var bool
     */
    public $refetch = false;


    /**
     * @var array виджет который отработает по умолчанию
     */
    public $widget = [];

    /**
     * Шаблон, верстка блока по умолчанию, которая задумана верстальщиком
     * @var string
     */
    public $defaultContent = '';



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

            if (is_string($this->id))
            {
                $modelInfoblock = \skeeks\cms\models\Infoblock::fetchByCode($this->id);
            } else if (is_int($this->id))
            {
                $modelInfoblock = \skeeks\cms\models\Infoblock::fetchById($this->id);
            }

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
                $result = $modelInfoblock->run($this->config);
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