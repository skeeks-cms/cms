<?php
/**
 * StaticBlock
 *
 * TODO: учитывать $sections
 * TODO: добавить кэширование
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets;
use skeeks\cms\base\Widget;

/**
 * Class Infoblock
 * @package skeeks\cms\widgets
 */
class StaticBlock extends Widget
{
    /**
     * @var string
     */
    public $code    = null;

    /**
     * @var string Значение по умолчанию
     */
    public $default = null;

    /**
     * Делать новый запрос в базу обязательно, или использовать сохраненное ранее значение
     * @var bool
     */
    public $refetch = false;

    /**
     * TODO: доработать, учитывать
     * @var string|array
     */
    public $sections = null;

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
        //Код не указан вернем значение по умолчанию
        if (!$this->code)
        {
            return $this->default;
        }

        $value = $this->getRegistered($this->code);

        if ($value === false || $this->refetch)
        {
            if (!$staticBlock = \skeeks\cms\models\StaticBlock::findByCode($this->code))
            {
                $value = $this->default;
            } else
            {
                $value = $staticBlock->multiValue;
            }

            self::$regsteredBlocks[$this->code] = $value;
        }

        return $value;
    }
}