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
    public $code = null;

    /**
     * TODO: доработать, учитывать
     * @var string|array
     */
    public $sections = null;

    /**
     * @return string
     */
    public function run()
    {
        if (!$this->code)
        {
            return '';
        } else
        {
            if (!$staticBlock = \skeeks\cms\models\StaticBlock::findByCode($this->code))
            {
                return '';
            }
        }

        if (!$this->sections)
        {
            if ($site = \Yii::$app->currentSite->get())
            {
                $this->sections = $site->id;
            }
        }

        return $staticBlock->getValue($this->sections);
    }
}