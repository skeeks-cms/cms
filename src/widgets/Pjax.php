<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */

namespace skeeks\cms\widgets;

/**
 * Class Pjax
 *
 * @package skeeks\cms\widgets
 */
class Pjax extends \yii\widgets\Pjax
{
    /**
     * Block container Pjax
     * @var bool
     */
    public $isBlock = true;

    /**
     * @var bool
     */
    public $isShowError = false;

    /**
     * @var bool
     */
    public $isShowNotifyError = false;

    /**
     * Block other container
     * @var string
     */
    public $blockContainer = '';

    /**
     * @var int
     */
    public $timeout = 30000;

    /**
     * @var string
     */
    public $clientCallbackSend = "";

    /**
     * @var string
     */
    public $clientCallbackComplete = "";

    /**
     * @var string
     */
    public $clientCallbackError = "";


    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        parent::registerClientScript();

        $errorMessage = \Yii::t('skeeks/admin', 'An unexpected error occurred. Refer to the developers.');

        $errorNotify = '';
        $error = '';
        if ($this->isShowNotifyError) {
            $errorNotify = "sx.notify.error('{$errorMessage}:<br />' + data.responseText);";
        }
        if ($this->isShowError) {

            $this->view->registerCss(<<<CSS
.pjax-errors {
    background: #fee5e5;
    padding: 15px;
    margin-top: 10px;
    border: 1px solid #f86c6b;
    border-radius: 2px;
}
.pjax-errors .red {
    color: red;
}
CSS
);
            $error = "
            var msg = '<span class=\"red\">{$errorMessage}</span><br />' + data.responseText;
            var jPjax = $('#{$this->id}');
            if ($('.pjax-errors', jPjax).length) {
                $('.pjax-errors', jPjax).empty().append(msg);
            } else {
                $('<div>', {'class' : 'pjax-errors'}).appendTo(jPjax).append(msg);
            }
            ";
        }

        if ($this->isBlock === true) {
            $this->getView()->registerJs(<<<JS
                (function(sx, $, _)
                {
                    var blockerPanel = new sx.classes.Blocker('#{$this->id}');

                    $(document).on('pjax:send', function(e)
                    {
                        if ('{$this->id}' == e.target.id) {
                            blockerPanel = new sx.classes.Blocker($(e.target));
                            blockerPanel.block();
                        }
                        
                    });

                    $(document).on('pjax:complete', function(e) {
                        if ('{$this->id}' == e.target.id) {
                            blockerPanel.unblock();
                        }
                    });

                    $(document).on('pjax:error', function(e, data) {
                        if ('{$this->id}' == e.target.id) {
                            {$errorNotify}
                            {$error}
                            blockerPanel.unblock();
                            e.preventDefault();
                        }
                    });

                })(sx, sx.$, sx._);
JS
            );
        }

        if ($this->blockContainer) {
            $this->getView()->registerJs(<<<JS
                (function(sx, $, _)
                {
                    var blockerPanel = new sx.classes.Blocker($("{$this->blockContainer}"));

                    $(document).on('pjax:send', function(e)
                    {
                        if ('{$this->id}' == e.target.id) {
                            var blockerPanel = new sx.classes.Blocker($("{$this->blockContainer}"));
                            blockerPanel.block();
                        }
                        
                    });

                    $(document).on('pjax:complete', function(e) {
                        if ('{$this->id}' == e.target.id) {
                            blockerPanel.unblock();
                        }  
                        
                    });

                    $(document).on('pjax:error', function(e, data) {
                        if ('{$this->id}' == e.target.id) {
                            {$errorNotify}
                            {$error}
                            blockerPanel.unblock();
                            e.preventDefault();
                        }
                        
                    });

                })(sx, sx.$, sx._);
JS
            );
        }


        if ($this->clientCallbackSend) {

            $clientCallbackSend = $this->clientCallbackSend;
            $this->getView()->registerJs(<<<JS
             
                $(document).on('pjax:send', function(e, data)
                {
                    var success = {$clientCallbackSend};
                    if ('{$this->id}' == e.target.id) {
                        success(e, data);
                    }
                    
                });
JS
            );
        }

    }
}