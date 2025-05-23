<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $error string */
/* @var $widget \common\modules\work\widgets\ScheduleBtnWidget */

$widget = $this->context;
$user = $widget->user;

\skeeks\cms\backend\widgets\assets\ControllerActionsWidgetAsset::register($this);
\skeeks\cms\backend\widgets\AjaxControllerActionsWidget::registerAssets();
?>

<?
/**
 * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
 */
/*$qNotifiesNotPopups = \Yii::$app->user->identity->getCmsWebNotifies()->notPopup()->limit(3)->all();
$qNotifiesNotPopupsArray = [];
if ($qNotifiesNotPopups) {
    foreach ($qNotifiesNotPopups as $qNotifiesNotPopup)
    {
        $qNotifiesNotPopupsArray[] = \yii\helpers\ArrayHelper::merge(['render' => $qNotifiesNotPopup->getHtml()], $qNotifiesNotPopup->toArray());
    }
}*/

$qNotifies = \Yii::$app->user->identity->getCmsWebNotifies()->notRead();
$notReaded = $qNotifies->count();
$lastNotify = $qNotifies->limit(1)->one();

$jsData = [
    'backend_notifies' => \yii\helpers\Url::to(['/cms/ajax/web-notifies']),
    'backend_notifies_new' => \yii\helpers\Url::to(['/cms/ajax/web-notifies-new']),
    'backend_notifies_clear' => \yii\helpers\Url::to(['/cms/ajax/web-notifies-clear']),
    'id' => "sx-notifies-wrapper",
    'sound_src' => \skeeks\cms\assets\CmsAsset::getAssetUrl("sound/sound_telegram.mp3"),
    'last_notify_id' => $lastNotify ? $lastNotify->id : ''
];
$js = \yii\helpers\Json::encode($jsData);

$this->registerJs(<<<JS
(function(sx, $, _)
{
sx.classes.WebNotify = sx.classes.Component.extend({

    _init: function()
    {
        var self = this;
        
        self.jBage = $(".sx-bage-notifies", self.getJWrapper());
        self.jTrigger = $(".sx-trigger-notifies", self.getJWrapper());
        self.jContainer = $(".sx-notifies", self.getJWrapper());
        self.jEmptyContainer = $(".sx-empty", self.getJWrapper());
        self.jItemsContainer = $(".sx-notifies-has-items", self.getJWrapper());
        self.jList = $(".sx-notifies-list", self.getJWrapper());
        self.jBtnClear = $(".sx-btn-clear", self.getJWrapper());
        
        self.jTrigger.on("click", function() {
            self.jContainer.fadeIn();
            self.loadMessages();
        });
        
        self.jBtnClear.on("click", function() {
            self.jContainer.fadeOut();
            self.clearMessages();
        });
        
        
        $(document).mouseup(function (e) {
                
            var isClose = true;
                if ($(e.target).closest(".sx-notifies-wrapper").length != 0){ 
                 isClose = false;
              }
                
            
                if (isClose) {
                    self.jContainer.fadeOut();
                }
            
        });
    
        setInterval(function()
        {
            self.checkNewNotifies();
        }, 10000);
    },
    
    checkNewNotifies: function() {
        var self = this;
        
        var ajaxQuery = sx.ajax.preparePostQuery(self.get("backend_notifies_new"), {
            'last_notify_id' : self.get('last_notify_id')
        });
        
        var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
            'blockerSelector' : self.jContainer,
            'enableBlocker': false,
            'allowResponseSuccessMessage': false,
            'allowResponseErrorMessage': false,
            'ajaxExecuteErrorAllowMessage': false,
        });
        
        ajaxHandler.on('error', function (e, response) {
            /*console.log(response.data);*/
        });

        ajaxHandler.on('success', function (e, response) {
            if (response.data.items.length) {
                response.data.items.forEach(function(item) {
                    /*sx.notify.info(item.render, { life: 1000000 });*/
                    sx.notify.info(item.render);
                    self.set("last_notify_id", item.id);
                    var audio = new Audio();
                    audio.src = self.get("sound_src");
                    audio.play();
                });
            }
            
            if (response.data.total > 0) {
                self.jBage.empty().append(response.data.total);
                self.getJWrapper().addClass("sx-has-notifies");
            } else {
                self.jBage.empty();
                self.getJWrapper().removeClass("sx-has-notifies");
            }
        });

        ajaxQuery.execute();
    },
    
    loadMessages: function() {
        var self = this;
        
        var ajaxQuery = sx.ajax.preparePostQuery(self.get("backend_notifies"));
        
        var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
            'blockerSelector' : self.jContainer,
            'enableBlocker': true,
            'allowResponseSuccessMessage': false,
            'allowResponseErrorMessage': false,
            'ajaxExecuteErrorAllowMessage': false,
        });
        
        ajaxHandler.on('error', function (e, response) {
            /*console.log(response.data);*/
        });

        ajaxHandler.on('success', function (e, response) {
            
            if (response.data.items.length) {
                self.jEmptyContainer.hide();
                self.jItemsContainer.show();
                
                self.jList.empty();
                response.data.items.forEach(function(item) {
                    self.jList.append(item.render);
                });
            } else {
                self.jEmptyContainer.show();
                self.jList.hide();
            }
            
            if (response.data.total > 0) {
                self.jBage.empty().append(response.data.total);
                self.getJWrapper().addClass("sx-has-notifies");
            } else {
                self.jBage.empty();
                self.getJWrapper().removeClass("sx-has-notifies");
                
            }
        });

        ajaxQuery.execute();
    },
    
    clearMessages: function() {
        var self = this;
        
        var ajaxQuery = sx.ajax.preparePostQuery(self.get("backend_notifies_clear"));
        
        var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
            'blockerSelector' : self.jContainer,
            'enableBlocker': true,
            'allowResponseSuccessMessage': false,
            'allowResponseErrorMessage': false,
            'ajaxExecuteErrorAllowMessage': false,
        });
        
        ajaxHandler.on('error', function (e, response) {
            /*console.log(response.data);*/
        });

        ajaxHandler.on('success', function (e, response) {
            self.jItemsContainer.hide();
            self.jEmptyContainer.show();
            self.jList.hide();
            
        });

        ajaxQuery.execute();
    },
    
    getJWrapper: function() {
        return $("#" + this.get("id"));
    }

});

new sx.classes.WebNotify({$js});
})(sx, sx.$, sx._);
JS
);
$this->registerCss(<<<CSS
.sx-notifies .sx-notifies-btns {
    display: flex;
    justify-content: right;
    margin-top: 1rem;
}


.sx-notifies .sx-model .sx-action-trigger {
    cursor: pointer;
    border-bottom: 1px solid;
}
.sx-notifies .sx-notifies-list .sx-item .sx-time {
    color: #8294b9;
}
.sx-notifies .sx-notifies-list .sx-item.sx-not-read {
    margin-left: 1rem;
}
.sx-notifies .sx-notifies-list .sx-item.sx-not-read:before {
    content: "•";
    position: absolute;
    left: -1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-red-pale);
}
.sx-notifies .sx-notifies-list .sx-item {
    margin-bottom: 1rem;
    position: relative;
}
.sx-notifies .sx-notifies-list .sx-item:last-child {
    margin-bottom: 0;
}

.sx-notifies {
    background: #1d1d1d !important;
    /*background: white !important;*/
    width: 25rem;
    display: none;
    position: absolute;
    top: 4rem;
    right: -2rem;
    min-height: 4rem;
    border-radius: var(--border-radius);
    padding: 0.5rem;
    box-shadow: 0 7px 21px rgba(83, 92, 105, .12), 0 -1px 6px 0 rgba(83, 92, 105, .06);
}


.sx-notifies-wrapper .sx-notifies-has-items,
.sx-notifies-wrapper .sx-empty {
    display: none;
    
}

.sx-notifies-wrapper .sx-empty {
    text-align: center;
}


.sx-notifies-wrapper .dropdown-toggle::after {
    content: none;
}

.sx-notifies-wrapper .sx-notifies-list {
    max-height: 70vh;
    overflow-y: auto;
}

.sx-notifies-wrapper .sx-notifies-list,
.sx-notifies-wrapper .sx-empty {
    padding: 1rem;
}

.sx-notifies-wrapper .sx-bage-notifies {
    display: none;
    transition: all .2s;
    animation: sx-pulse-bage 1.5s infinite linear;
}

.sx-notifies-wrapper.sx-has-notifies .sx-bage-notifies {
    display: block;
}

@keyframes sx-pulse-bage {
  0% {
    box-shadow: 0 0 5px 0px var(--color-red), 0 0 5px 0px var(--color-red); 
  }
  100% {
    box-shadow: 0 0 5px 6px rgba(255, 48, 26, 0), 0 0 4px 10px rgba(255, 48, 26, 0); 
  } 
}

CSS
);


?>

<div class="sx-btn-backend-header sx-notifies-wrapper <?php echo $notReaded ? "sx-has-notifies" : ""; ?>" id="sx-notifies-wrapper">
    <a class="d-block sx-trigger-notifies" href="#">
        <span class="sx-badge sx-bage-notifies"><?php echo $notReaded; ?></span>
        <i class="hs-admin-bell g-absolute-centered"></i>
    </a>
    <div class="sx-notifies">
        <div class="sx-notifies-has-items">
            <div class="sx-notifies-list">

            </div>
            <div class="sx-notifies-btns">
                <button class="btn btn-primary btn-xs sx-btn-clear">Очистить</button>
            </div>
        </div>

        <div class="sx-empty">Все уведомления прочитаны</div>
    </div>
</div>




