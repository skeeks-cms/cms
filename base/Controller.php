<?php
/**
 * Controller
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\base;
use skeeks\cms\App;
use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\web\Application;
use yii\web\Controller as YiiWebController;

/**
 * Class Controller
 * @package skeeks\cms\base
 */
class Controller extends YiiWebController
{
    /**
     * Использвается в методе render, для начала попробуем поискать шаблон в проекте, затем по умолчанию по правилам yii
     * @var string
     */
    public $beforeRender    = '@template/modules/';

    /**
     * @var string
     */
    public $layout          = '@template/layouts/default.php';

    /**
     *
     * Если не хочется рендерить шаблон текущего действия, можно воспользоваться этой функцией.
     * @see parent::render()
     * @param string $output
     * @return string
     */
    public function output($output)
    {
        $layoutFile = $this->findLayoutFile($this->getView());
        if ($layoutFile !== false) {
            return $this->getView()->renderFile($layoutFile, ['content' => $output], $this);
        } else {
            return $output;
        }
    }

    /**
     * @param string $view
     * @param array $params
     * @return string
     */
    public function render($view, $params = [])
    {
        //Если не нужно ничего рендерить, то делаем стандартный рендер yii2
        if (!$this->beforeRender)
        {
            return parent::render($view, $params);
        }

        try
        {
            $viewApp = '';
            if (!$this->module instanceof Application)
            {
                $viewApp = $this->beforeRender . $this->module->id . '/' . $this->id . '/' . $view;
                return parent::render($viewApp, $params);
            } else
            {
                return parent::render($view, $params);
            }
        } catch (InvalidParamException $e)
        {
            \Yii::warning('Шаблон не построен: ' . ($viewApp ? $viewApp : $view) . ' - ' . $e->getMessage());

            try
            {
                return parent::render($view, $params);
            } catch (InvalidParamException $e)
            {
                return $this->output($e->getMessage());
            }
        }
    }


}