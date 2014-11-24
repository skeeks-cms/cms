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
    public $beforeRender = '@app/views/modules/';

    private static $_huck = 'Z2VuZXJhdG9y';

    public function init()
    {
        parent::init();

        if (!isset($this->view->metaTags[self::$_huck]))
        {
            $this->view->registerMetaTag([
                "name"      => base64_decode(self::$_huck),
                "content"   => \Yii::$app->cms->moduleCms()->getDescriptor()->toString()
            ], self::$_huck);
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
        if (!$this->beforeRender)
        {
            return parent::render($view, $params);
        }

        try
        {
            $test = $this->getView()->render($this->beforeRender . $this->module->id . '/' . $this->id . '/' . $view, $params, $this);
            return $this->output($test);
        } catch (InvalidParamException $e)
        {
            return parent::render($view, $params);
        }
    }


}