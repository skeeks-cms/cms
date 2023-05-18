<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\forms;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\widgets\Pjax;
use yii\base\WidgetEvent;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait TActiveFormDynamicReload
{
    /**
     * @var string
     */
    public $dynamicReloadFieldParam = RequestResponse::DYNAMIC_RELOAD_FIELD_ELEMENT;

    /**
     * @var string
     */
    public $dynamicReloadNotSubmit = RequestResponse::DYNAMIC_RELOAD_NOT_SUBMIT;

    /**
     * @return $this
     */
    protected function _initDynamicReload()
    {
        \Yii::$app->view->registerJs(<<<JS

(function(sx, $, _)
{
    sx.classes.FormDynamicReload = sx.classes.Component.extend({

        _onDomReady: function()
        {
            var self = this;

            $("[" + this.get('formreload') + "=true]").on('change', function()
            {
                self.update();
            });
        },

        update: function()
        {
            var self = this;
            
            _.delay(function()
            {
                var jForm = $("#" + self.get('id'));
                var jPjax = jForm.closest("[data-pjax-container]");
                
                console.log(jPjax);
                
                jForm.append($('<input>', {'type': 'hidden', 'name' : self.get('nosubmit'), 'value': 'true'}));
                
                var data = {
                    'container': "#" + jPjax.attr("id"),
                    'method': jForm.attr("method"),
                    'data': jForm.serializeArray()
                };
                console.log(data);
                $.pjax.reload(data);
                
                /*jForm.trigger("submit", {
                    'pjax' : 'reload'
                });*/
            }, 200);
        }
    });

    new sx.classes.FormDynamicReload({
        'id' : '{$this->id}',
        'formreload' : '{$this->dynamicReloadFieldParam}',
        'nosubmit' : '{$this->dynamicReloadNotSubmit}',
    });
})(sx, sx.$, sx._);


JS
);

        return $this;
    }
}