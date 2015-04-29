<?php
/**
 * Pjax
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\widgets;

/**
 * Class Pjax
 * @package skeeks\cms\modules\admin\widgets
 */
class Pjax extends \yii\widgets\Pjax
{
    /**
     * Блокировать контейнер Pjax
     * @var bool
     */
    public $blockPjaxContainer     = true;

    /**
     * Блокировать другой контейнер
     * @var string
     */
    public $blockContainer          = '';

    /**
     * @var int
     */
    public $timeout = 10000;


    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        parent::registerClientScript();

        if ($this->blockPjaxContainer === true)
        {
            $this->getView()->registerJs(<<<JS
            (function(sx, $, _)
            {
                var blockerPanel = new sx.classes.Blocker('.sx-panel');

                $(document).on('pjax:send', function(e)
                {
                    var blockerPanel = new sx.classes.Blocker($(e.target));
                    blockerPanel.block();
                })

                $(document).on('pjax:complete', function(e) {
                    blockerPanel.unblock();
                })

            })(sx, sx.$, sx._);
JS
        );
        }

        if ($this->blockContainer)
        {
            $this->getView()->registerJs(<<<JS
            (function(sx, $, _)
            {
                var blockerPanel = new sx.classes.Blocker($("{$this->blockContainer}"));

                $(document).on('pjax:send', function(e)
                {
                    var blockerPanel = new sx.classes.Blocker($("{$this->blockContainer}"));
                    blockerPanel.block();
                })

                $(document).on('pjax:complete', function(e) {
                    blockerPanel.unblock();
                })

            })(sx, sx.$, sx._);
JS
        );
        }

    }
}