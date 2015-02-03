<?php
/**
 * Виджет который может использовать для рендеринга шаблона
 * Обычно шаблон описан в декскрипторе
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\base\hasTemplate;

use skeeks\cms\base\Widget;
use skeeks\sx\Entity;
use Yii;
use yii\base\Exception;

/**
 * Class WidgetHasTemplate
 * @package skeeks\cms\widgets\base\hasTemplate
 */
abstract class WidgetHasTemplate extends Widget
{
    /**
     * @var null|string
     */
    public $template                 = 'default';

    /**
     * @var Entity
     */
    protected $_data = null;
    protected $_binded = null;

    public function init()
    {
        parent::init();
        $this->_data = new Entity();
    }

    /**
     * Формирование данных для шаблона
     * @return $this
     */
    public function bind()
    {
        return $this;
    }

    /**
     * @return string
     */
    public function run()
    {
        if ($this->_binded === null)
        {
            $this->bind();
            $this->_binded = true;
        }

        $this->_data->set('widget', $this);

        $result = '';
        try
        {
            $template = $this->getDescriptor()->getTemplatesObject()->getComponent($this->template);
            if ($template)
            {
                if ($template->baseDir)
                {
                    $result = $this->renderFile($template->baseDir . DIRECTORY_SEPARATOR . $this->template . '.php', $this->_data->toArray());
                } else
                {
                    $result = $this->render($this->template, $this->_data->toArray());
                }
            } else
            {
                $result = $this->render($this->template, $this->_data->toArray());
            }
        } catch (\Exception $e)
        {
            $result = '';
        }

        return $result;
    }


}
