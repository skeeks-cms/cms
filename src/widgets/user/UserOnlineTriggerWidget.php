<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\user;

use common\models\User;
use skeeks\cms\widgets\user\assets\UserOnlineWidgetAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Виджет отправляющий ajax запросы в бэкенд с заданной частотой
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UserOnlineTriggerWidget extends Widget
{
    /**
     * @var array
     */
    public $clientOptions = [];

    /**
     * @var int
     */
    public $delay = 30;

    public function run()
    {
        $this->clientOptions['id'] = $this->id;
        $this->clientOptions['url'] = Url::to('/cms/online/trigger');

        $delay = $this->delay * 1000;

        $jsOptions = Json::encode($this->clientOptions);

        $this->view->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.Online = sx.classes.Component.extend({

        _onDomReady: function()
        {
            var self = this;
            setInterval(function(){
                self.request();
            }, this.get('delay', {$delay}))
        },


        request: function()
        {
            // Using YQL and JSONP
            $.ajax({
                url: this.get('url'),

                // The name of the callback parameter, as specified by the YQL service
                jsonp: "callback",

                // Tell jQuery we're expecting JSONP
                dataType: "jsonp",

                // Tell YQL what we want and that we want JSON
                data: {
                    q: "select title,abstract,url from search.news where query=\"cat\"",
                    format: "json"
                },

                // Work with the response
                success: function( response ) {
                    console.log( response ); // server response
                }
            });
        }
    });

    new sx.classes.Online({$jsOptions});
})(sx, sx.$, sx._);
JS
);
        return '';
    }
}