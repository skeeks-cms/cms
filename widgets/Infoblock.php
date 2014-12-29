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
                $result = '';
                return $result;
            }

            $result = $modelInfoblock->run($this->config);
            self::$regsteredBlocks[$this->id] = $result;
        }

        return $result;
    }
}