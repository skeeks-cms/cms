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
use skeeks\cms\helpers\FileHelper;
use skeeks\sx\File;
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
     * @var array
     */
    public $beforeRender    = ['@template/modules/', '@templateDefault/modules/'];

    /**
     * @var array возможные лейоуты, поиск в шаблонах
     */
    public $layouts          = ['@template/layouts/default.php', '@templateDefault/layouts/default.php'];

    public function init()
    {
        parent::init();

        //Установка layout-a
        if ($file = FileHelper::getFirstExistingFileArray($this->layouts))
        {
            $this->layout = $file;
        }
    }

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

        if (is_string($this->beforeRender))
        {
            $this->beforeRender = [$this->beforeRender];
        }

        //Возможные пути к файлу шаблона
        $viewFilePaths = [];
        foreach ($this->beforeRender as $path)
        {
            if (!$this->module instanceof Application)
            {
                $viewFilePaths[] = $path . $this->module->id . '/' . $this->id . '/' . $view . ".php";
            } else
            {
                $viewFilePaths[] = $path . $this->id . '/' . $view . ".php";
            }
        }

        if ($viewFile = FileHelper::getFirstExistingFileArray($viewFilePaths))
        {
            try
            {
                return parent::render($viewFile, $params);

            } catch (InvalidParamException $e)
            {

                \Yii::warning('Ошибка в шаблоне: ' . $viewFile . ' - ' . $e->getMessage());
                return $this->output($e->getMessage());
            }
        } else
        {
            try
            {
                return parent::render($view, $params);
            } catch (InvalidParamException $e)
            {
                $message = "Шаблоные не найдены: " . implode(', ', $viewFilePaths);
                $message .= $e->getMessage();
                return $this->output($e->getMessage());
            }
        }


    }


}