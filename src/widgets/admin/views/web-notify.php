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
    'browser_icon_src' => \skeeks\cms\assets\CmsAsset::getAssetUrl("favicon.ico"),
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
        self.jBrowserPermission = $(".sx-browser-permission", self.getJWrapper());
        self.jBrowserPermissionEnable = $(".sx-browser-permission-enable", self.getJWrapper());
        self.jBrowserPermissionDenied = $(".sx-browser-permission-denied", self.getJWrapper());
        self.jBtnBrowserPermission = $(".sx-btn-browser-permission", self.getJWrapper());
        
        self.jTrigger.on("click", function(e) {
            e.preventDefault();
            self.updateBrowserPermissionPanel();
            self.jContainer.fadeIn();
            self.loadMessages();
        });

        self.jBtnBrowserPermission.on("click", function(e) {
            e.preventDefault();
            self.requestBrowserPermission();
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

        self.updateBrowserPermissionPanel();
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
                    self.showBrowserNotification(item);
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

    isBrowserNotificationsSupported: function() {
        return "Notification" in window;
    },

    requestBrowserPermission: function() {
        var self = this;

        if (!self.isBrowserNotificationsSupported() || Notification.permission !== "default") {
            self.updateBrowserPermissionPanel();
            return;
        }

        var permission = Notification.requestPermission();
        if (permission && permission.then) {
            permission.then(function() {
                self.updateBrowserPermissionPanel();
            });
        } else {
            self.updateBrowserPermissionPanel();
        }
    },

    updateBrowserPermissionPanel: function() {
        var self = this;

        if (!self.jBrowserPermission || !self.jBrowserPermission.length) {
            return;
        }

        if (!self.isBrowserNotificationsSupported() || Notification.permission === "granted") {
            self.jBrowserPermission.hide();
            return;
        }

        self.jBrowserPermission.show();

        if (Notification.permission === "denied") {
            self.jBrowserPermissionEnable.hide();
            self.jBrowserPermissionDenied.show();
        } else {
            self.jBrowserPermissionDenied.hide();
            self.jBrowserPermissionEnable.show();
        }
    },

    getBrowserStorageKey: function(item) {
        return "sx-web-notify-browser-" + item.id;
    },

    isBrowserNotificationShown: function(item) {
        if (!item || !item.id) {
            return false;
        }

        try {
            return window.localStorage && window.localStorage.getItem(this.getBrowserStorageKey(item));
        } catch (e) {
            return false;
        }
    },

    markBrowserNotificationShown: function(item) {
        if (!item || !item.id) {
            return;
        }

        try {
            if (window.localStorage) {
                window.localStorage.setItem(this.getBrowserStorageKey(item), "1");
            }
        } catch (e) {}
    },

    getBrowserNotificationData: function(item) {
        var jRender = $("<div>").html(item.render || "");
        var title = $.trim(item.name || jRender.find(".sx-name").first().text() || document.title);
        var body = $.trim(jRender.find(".sx-model").first().text());

        if (!body) {
            body = $.trim(jRender.text().replace(title, ""));
        }

        body = body.replace(/\s+/g, " ");

        return {
            title: title,
            body: body.substr(0, 240)
        };
    },

    showBrowserNotification: function(item) {
        var self = this;

        if (!self.isBrowserNotificationsSupported() || Notification.permission !== "granted" || self.isBrowserNotificationShown(item)) {
            return;
        }

        var notificationData = self.getBrowserNotificationData(item);
        self.markBrowserNotificationShown(item);

        try {
            var notification = new Notification(notificationData.title, {
                body: notificationData.body,
                icon: self.get("browser_icon_src"),
                tag: "sx-web-notify-" + item.id,
                renotify: true
            });

            notification.onclick = function() {
                window.focus();
                notification.close();
            };

            setTimeout(function() {
                notification.close();
            }, 10000);
        } catch (e) {}
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

.sx-notifies-wrapper .sx-browser-permission {
    display: none;
    margin: 0.5rem;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    background: rgba(43, 123, 220, 0.16);
    color: #fff;
}

.sx-notifies-wrapper .sx-browser-permission-enable,
.sx-notifies-wrapper .sx-browser-permission-denied {
    display: none;
}

.sx-notifies-wrapper .sx-browser-permission-denied {
    border-left: 3px solid var(--color-red-pale);
    padding-left: 0.75rem;
}

.sx-notifies-wrapper .sx-browser-permission-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.sx-notifies-wrapper .sx-browser-permission-text {
    color: #b8c7e6;
    font-size: 0.875rem;
    line-height: 1.35;
    margin-bottom: 0.65rem;
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
        <div class="sx-browser-permission">
            <div class="sx-browser-permission-enable">
                <div class="sx-browser-permission-title">&#1041;&#1088;&#1072;&#1091;&#1079;&#1077;&#1088;&#1085;&#1099;&#1077; &#1091;&#1074;&#1077;&#1076;&#1086;&#1084;&#1083;&#1077;&#1085;&#1080;&#1103;</div>
                <div class="sx-browser-permission-text">&#1056;&#1072;&#1079;&#1088;&#1077;&#1096;&#1080;&#1090;&#1077; &#1091;&#1074;&#1077;&#1076;&#1086;&#1084;&#1083;&#1077;&#1085;&#1080;&#1103;, &#1095;&#1090;&#1086;&#1073;&#1099; &#1074;&#1080;&#1076;&#1077;&#1090;&#1100; &#1085;&#1086;&#1074;&#1099;&#1077; &#1089;&#1086;&#1073;&#1099;&#1090;&#1080;&#1103; &#1076;&#1072;&#1078;&#1077; &#1074; &#1076;&#1088;&#1091;&#1075;&#1086;&#1081; &#1074;&#1082;&#1083;&#1072;&#1076;&#1082;&#1077;.</div>
                <button class="btn btn-primary btn-xs sx-btn-browser-permission">&#1042;&#1082;&#1083;&#1102;&#1095;&#1080;&#1090;&#1100;</button>
            </div>
            <div class="sx-browser-permission-denied">
                <div class="sx-browser-permission-title">&#1059;&#1074;&#1077;&#1076;&#1086;&#1084;&#1083;&#1077;&#1085;&#1080;&#1103; &#1079;&#1072;&#1073;&#1083;&#1086;&#1082;&#1080;&#1088;&#1086;&#1074;&#1072;&#1085;&#1099;</div>
                <div class="sx-browser-permission-text">&#1056;&#1072;&#1079;&#1088;&#1077;&#1096;&#1080;&#1090;&#1077; &#1091;&#1074;&#1077;&#1076;&#1086;&#1084;&#1083;&#1077;&#1085;&#1080;&#1103; &#1076;&#1083;&#1103; &#1101;&#1090;&#1086;&#1075;&#1086; &#1089;&#1072;&#1081;&#1090;&#1072; &#1074; &#1085;&#1072;&#1089;&#1090;&#1088;&#1086;&#1081;&#1082;&#1072;&#1093; &#1073;&#1088;&#1072;&#1091;&#1079;&#1077;&#1088;&#1072;.</div>
            </div>
        </div>
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




