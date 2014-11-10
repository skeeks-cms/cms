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

/**
 * Class Infoblock
 * @package skeeks\cms\widgets
 */
class Infoblock extends Widget
{
    /**
     * @var null|int
     */
    public $id = null;

    /**
     * @var null|string
     */
    public $code = null;

    /**
     * Дополнительные настройки
     * @var array
     */
    public $config = [];


    /**
     * @return string
     */
    public function run()
    {
        $result = "";

        if ($this->id)
        {
            $modelInfoblock = \skeeks\cms\models\Infoblock::findById($this->id);
        } else if ($this->code)
        {
            $modelInfoblock = \skeeks\cms\models\Infoblock::findByCode($this->code);
        }

        if (!$modelInfoblock)
        {
            return $result;
        }

        return $modelInfoblock->run($this->config);
    }
}