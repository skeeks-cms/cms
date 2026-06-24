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
    'backend_idle_work_check' => \yii\helpers\Url::to(['/cms/ajax/idle-work-check']),
    'backend_idle_work_stop' => \yii\helpers\Url::to(['/cms/ajax/idle-work-stop']),
    'backend_stale_work_check' => \yii\helpers\Url::to(['/cms/ajax/stale-work-check']),
    'backend_stale_work_stop' => \yii\helpers\Url::to(['/cms/ajax/stale-work-stop']),
    'id' => "sx-notifies-wrapper",
    'idle_modal_id' => "sx-idle-work-modal",
    'stale_modal_id' => "sx-stale-work-modal",
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
        self.jIdleModal = $("#" + self.get("idle_modal_id"));
        if (self.jIdleModal.parent()[0] !== document.body) {
            self.jIdleModal.appendTo("body");
        }
        self.jIdleModalBody = $(".sx-idle-work-body", self.jIdleModal);
        self.jIdleModalBtnYes = $(".sx-idle-work-yes", self.jIdleModal);
        self.jIdleModalBtnNo = $(".sx-idle-work-no", self.jIdleModal);
        self.idleWorkData = null;
        self.isIdleWorkStopped = false;
        self.jStaleModal = $("#" + self.get("stale_modal_id"));
        if (self.jStaleModal.parent()[0] !== document.body) {
            self.jStaleModal.appendTo("body");
        }
        self.jStaleModalBody = $(".sx-stale-work-body", self.jStaleModal);
        self.jStaleModalInput = $(".sx-stale-work-end-time", self.jStaleModal);
        self.jStaleModalError = $(".sx-stale-work-error", self.jStaleModal);
        self.jStaleModalBtnSave = $(".sx-stale-work-save", self.jStaleModal);
        self.jStaleModalBtnLater = $(".sx-stale-work-later", self.jStaleModal);
        self.staleWorkData = null;
        self.isStaleWorkStopped = false;
        
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

        self.jIdleModalBtnYes.on("click", function(e) {
            e.preventDefault();
            self.stopIdleWork();
        });

        self.jIdleModalBtnNo.on("click", function(e) {
            e.preventDefault();
            self.snoozeIdleWorkReminder();
            self.jIdleModal.modal("hide");
        });

        self.jIdleModal.on("hidden.bs.modal", function() {
            if (self.idleWorkData && !self.isIdleWorkStopped) {
                self.snoozeIdleWorkReminder();
            }
        });

        self.jStaleModalBtnSave.on("click", function(e) {
            e.preventDefault();
            self.stopStaleWork();
        });

        self.jStaleModalBtnLater.on("click", function(e) {
            e.preventDefault();
            self.snoozeStaleWorkReminder();
            self.jStaleModal.modal("hide");
        });

        self.jStaleModal.on("hidden.bs.modal", function() {
            if (self.staleWorkData && !self.isStaleWorkStopped) {
                self.snoozeStaleWorkReminder();
            }
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

        setInterval(function()
        {
            self.checkWorkReminders();
        }, 60000);

        self.updateBrowserPermissionPanel();
        self.checkWorkReminders();
    },

    getCsrfData: function() {
        var data = {};
        if (window.yii && yii.getCsrfParam && yii.getCsrfToken) {
            data[yii.getCsrfParam()] = yii.getCsrfToken();
        }

        return data;
    },

    getIdleWorkStorageKey: function(data) {
        if (!data) {
            return "sx-idle-work-reminder";
        }

        return "sx-idle-work-reminder-" + data.schedule_id + "-" + data.last_task_end_at;
    },

    getIdleWorkSnoozedUntil: function(data) {
        try {
            return parseInt(window.localStorage.getItem(this.getIdleWorkStorageKey(data)), 10) || 0;
        } catch (e) {
            return 0;
        }
    },

    setIdleWorkSnoozedUntil: function(data, timestamp) {
        try {
            window.localStorage.setItem(this.getIdleWorkStorageKey(data), timestamp);
        } catch (e) {}
    },

    snoozeIdleWorkReminder: function() {
        if (!this.idleWorkData) {
            return;
        }

        this.setIdleWorkSnoozedUntil(this.idleWorkData, Date.now() + 10 * 60 * 1000);
    },

    getStaleWorkStorageKey: function(data) {
        if (!data) {
            return "sx-stale-work-reminder";
        }

        return "sx-stale-work-reminder-" + data.schedule_id + "-" + data.date_key;
    },

    getStaleWorkSnoozedUntil: function(data) {
        try {
            return parseInt(window.localStorage.getItem(this.getStaleWorkStorageKey(data)), 10) || 0;
        } catch (e) {
            return 0;
        }
    },

    setStaleWorkSnoozedUntil: function(data, timestamp) {
        try {
            window.localStorage.setItem(this.getStaleWorkStorageKey(data), timestamp);
        } catch (e) {}
    },

    snoozeStaleWorkReminder: function() {
        if (!this.staleWorkData) {
            return;
        }

        this.setStaleWorkSnoozedUntil(this.staleWorkData, Date.now() + 10 * 60 * 1000);
    },

    checkWorkReminders: function() {
        var self = this;

        self.checkStaleWork(function(hasStaleWork) {
            if (!hasStaleWork) {
                self.checkIdleWork();
            }
        });
    },

    checkStaleWork: function(callback) {
        var self = this;
        var data = self.getCsrfData();

        $.ajax({
            url: self.get("backend_stale_work_check"),
            type: "post",
            dataType: "json",
            data: data,
            success: function(response) {
                if (!response || !response.success || !response.data) {
                    if (callback) {
                        callback(false);
                    }
                    return;
                }

                if (self.getStaleWorkSnoozedUntil(response.data) > Date.now()) {
                    if (callback) {
                        callback(true);
                    }
                    return;
                }

                self.showStaleWorkModal(response.data);
                if (callback) {
                    callback(true);
                }
            },
            error: function() {
                if (callback) {
                    callback(false);
                }
            }
        });
    },

    renderStaleIntervals: function(title, intervals, emptyText) {
        var jWrap = $("<div>").addClass("sx-stale-work-section");
        jWrap.append($("<div>").addClass("sx-stale-work-section-title").text(title));

        if (!intervals || !intervals.length) {
            jWrap.append($("<div>").addClass("sx-stale-work-empty").text(emptyText));
            return jWrap;
        }

        intervals.forEach(function(interval) {
            var endTime = interval.end_time || "\u043d\u0435 \u0437\u0430\u043a\u0440\u044b\u0442";
            var duration = interval.duration ? " (" + interval.duration + ")" : "";
            var jItem = $("<div>").addClass("sx-stale-work-interval");
            if (interval.is_current || interval.is_open) {
                jItem.addClass("sx-is-open");
            }

            jItem.append(
                $("<div>").addClass("sx-stale-work-interval-time").text(interval.start_time + " \u2014 " + endTime + duration)
            );

            if (interval.task_name) {
                jItem.append($("<div>").addClass("sx-stale-work-task-name").text(interval.task_name));
            }

            jWrap.append(jItem);
        });

        return jWrap;
    },

    showStaleWorkModal: function(data) {
        var self = this;
        var title = data.is_yesterday
            ? "\u0412\u044b \u043d\u0435 \u0437\u0430\u0432\u0435\u0440\u0448\u0438\u043b\u0438 \u0440\u0430\u0431\u043e\u0447\u0435\u0435 \u0432\u0440\u0435\u043c\u044f \u0432\u0447\u0435\u0440\u0430"
            : "\u0412\u044b \u043d\u0435 \u0437\u0430\u0432\u0435\u0440\u0448\u0438\u043b\u0438 \u0440\u0430\u0431\u043e\u0447\u0435\u0435 \u0432\u0440\u0435\u043c\u044f";

        self.staleWorkData = data;
        self.isStaleWorkStopped = false;
        self.jStaleModalError.hide().empty();
        self.jStaleModalInput
            .attr("min", data.min_end_time)
            .attr("max", data.max_end_time)
            .val(data.min_end_time);

        $(".sx-stale-work-title", self.jStaleModal).text(title);
        self.jStaleModalBody.html(
            $("<div>").append(
                $("<p>").text("\u0414\u0435\u043d\u044c: " + data.work_date + ". \u0420\u0430\u0431\u043e\u0447\u0438\u0439 \u043f\u0440\u043e\u043c\u0435\u0436\u0443\u0442\u043e\u043a \u043d\u0430\u0447\u0430\u043b\u0441\u044f \u0432 " + data.start_datetime + "."),
                self.renderStaleIntervals(
                    "\u0420\u0430\u0431\u043e\u0447\u0438\u0435 \u043f\u0440\u043e\u043c\u0435\u0436\u0443\u0442\u043a\u0438",
                    data.work_intervals,
                    "\u041d\u0435\u0442 \u043f\u0440\u043e\u043c\u0435\u0436\u0443\u0442\u043a\u043e\u0432."
                ),
                self.renderStaleIntervals(
                    "\u0420\u0430\u0431\u043e\u0442\u0430 \u043f\u043e \u0437\u0430\u0434\u0430\u0447\u0430\u043c",
                    data.task_intervals,
                    "\u0417\u0430\u0434\u0430\u0447\u0438 \u0432 \u044d\u0442\u043e\u0442 \u0434\u0435\u043d\u044c \u043d\u0435 \u0437\u0430\u043f\u0443\u0441\u043a\u0430\u043b\u0438\u0441\u044c."
                ),
                $("<p>").addClass("sx-stale-work-hint").text("\u0423\u043a\u0430\u0436\u0438\u0442\u0435, \u0432\u043e \u0441\u043a\u043e\u043b\u044c\u043a\u043e \u043d\u0443\u0436\u043d\u043e \u0437\u0430\u0432\u0435\u0440\u0448\u0438\u0442\u044c \u0440\u0430\u0431\u043e\u0447\u0438\u0439 \u0434\u0435\u043d\u044c. \u041c\u0438\u043d\u0438\u043c\u0443\u043c: " + data.min_end_time + ".")
            ).html()
        );

        self.jStaleModal.modal({
            backdrop: "static",
            keyboard: false,
            show: true
        });
    },

    stopStaleWork: function() {
        var self = this;
        var data = self.getCsrfData();
        data.end_time = self.jStaleModalInput.val();

        self.jStaleModalError.hide().empty();
        self.jStaleModalBtnSave.prop("disabled", true);
        self.jStaleModalBtnLater.prop("disabled", true);

        $.ajax({
            url: self.get("backend_stale_work_stop"),
            type: "post",
            dataType: "json",
            data: data,
            complete: function() {
                self.jStaleModalBtnSave.prop("disabled", false);
                self.jStaleModalBtnLater.prop("disabled", false);
            },
            success: function(response) {
                if (!response || !response.success) {
                    self.jStaleModalError.text(response && response.error ? response.error : "\u041d\u0435 \u0443\u0434\u0430\u043b\u043e\u0441\u044c \u0438\u0441\u043f\u0440\u0430\u0432\u0438\u0442\u044c \u0440\u0430\u0431\u043e\u0447\u0435\u0435 \u0432\u0440\u0435\u043c\u044f.").show();
                    return;
                }

                self.isStaleWorkStopped = true;
                self.jStaleModal.modal("hide");
                self.setStaleWorkSnoozedUntil(self.staleWorkData, Date.now() + 24 * 60 * 60 * 1000);
                sx.notify.success("\u0420\u0430\u0431\u043e\u0447\u0435\u0435 \u0432\u0440\u0435\u043c\u044f \u0438\u0441\u043f\u0440\u0430\u0432\u043b\u0435\u043d\u043e.");
                if ($.pjax && $("#sx-schedule-pjax").length) {
                    $.pjax.reload("#sx-schedule-pjax", {async: false});
                }
            }
        });
    },

    checkIdleWork: function() {
        var self = this;
        var data = self.getCsrfData();

        $.ajax({
            url: self.get("backend_idle_work_check"),
            type: "post",
            dataType: "json",
            data: data,
            success: function(response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                if (self.getIdleWorkSnoozedUntil(response.data) > Date.now()) {
                    return;
                }

                self.showIdleWorkModal(response.data);
            }
        });
    },

    showIdleWorkModal: function(data) {
        var self = this;
        var message;
        var closeTime = data.last_task_date || data.last_task_time;
        self.idleWorkData = data;
        self.isIdleWorkStopped = false;
        if (data.reason === "no_tasks") {
            message = "Вы включили рабочее время, но не запускали задачи уже " + data.idle_duration + ".";
        } else {
            message = "Вы последний раз работали по задаче в " + data.last_task_date + ".";
        }

        self.jIdleModalBody.html(
            $("<div>").append(
                $("<p>").addClass("sx-idle-work-message").text(message),
                $("<p>").append(
                    document.createTextNode("Завершить текущий рабочий период временем "),
                    $("<span>").addClass("sx-idle-work-time").text(closeTime),
                    document.createTextNode("?")
                )
            ).html()
        );
        self.jIdleModal.modal({
            backdrop: "static",
            keyboard: false,
            show: true
        });
    },

    stopIdleWork: function() {
        var self = this;
        var data = self.getCsrfData();

        self.jIdleModalBtnYes.prop("disabled", true);
        self.jIdleModalBtnNo.prop("disabled", true);

        $.ajax({
            url: self.get("backend_idle_work_stop"),
            type: "post",
            dataType: "json",
            data: data,
            complete: function() {
                self.jIdleModalBtnYes.prop("disabled", false);
                self.jIdleModalBtnNo.prop("disabled", false);
            },
            success: function(response) {
                if (!response || !response.success) {
                    sx.notify.error(response && response.error ? response.error : "Не удалось завершить рабочее время.");
                    return;
                }

                self.isIdleWorkStopped = true;
                self.jIdleModal.modal("hide");
                self.setIdleWorkSnoozedUntil(self.idleWorkData, Date.now() + 24 * 60 * 60 * 1000);
                sx.notify.success("Рабочее время завершено.");
                if ($.pjax && $("#sx-schedule-pjax").length) {
                    $.pjax.reload("#sx-schedule-pjax", {async: false});
                }
            }
        });
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

.sx-idle-work-modal .modal-body {
    color: #333;
    font-size: 16px;
    line-height: 1.45;
    min-height: auto;
    padding: 20px;
}

.sx-idle-work-modal .modal-title {
    color: #333;
    flex: 1 1 auto;
    font-size: 20px;
    line-height: 1.3;
    margin: 0;
}

.sx-idle-work-modal .modal-footer,
.sx-idle-work-modal .modal-header {
    padding: 15px 20px;
}

.sx-idle-work-modal .modal-header {
    align-items: center;
    display: flex;
    gap: 16px;
}

.sx-idle-work-modal .modal-header .close {
    flex: 0 0 auto;
    float: none;
    font-size: 24px;
    line-height: 1;
    margin: 0 0 0 auto;
    opacity: .55;
    order: 2;
}

.sx-idle-work-modal p {
    margin: 0 0 12px;
}

.sx-idle-work-modal p:last-child {
    margin-bottom: 0;
}

.sx-idle-work-modal .sx-idle-work-time {
    font-weight: 600;
}

.sx-stale-work-modal .modal-dialog {
    max-width: 720px;
    width: 720px;
}

.sx-stale-work-modal .modal-body {
    color: #333;
    font-size: 15px;
    line-height: 1.45;
    padding: 20px;
}

.sx-stale-work-modal .modal-title {
    color: #333;
    flex: 1 1 auto;
    font-size: 20px;
    line-height: 1.3;
    margin: 0;
}

.sx-stale-work-modal .modal-header {
    align-items: center;
    display: flex;
    gap: 16px;
    padding: 15px 20px;
}

.sx-stale-work-modal .modal-header .close {
    flex: 0 0 auto;
    float: none;
    font-size: 24px;
    line-height: 1;
    margin: 0 0 0 auto;
    opacity: .55;
    order: 2;
}

.sx-stale-work-modal .modal-footer {
    align-items: center;
    display: flex;
    gap: 10px;
    padding: 15px 20px;
}

.sx-stale-work-modal .sx-stale-work-form {
    align-items: center;
    display: flex;
    gap: 8px;
    margin-right: auto;
}

.sx-stale-work-modal .sx-stale-work-form label {
    color: #555;
    font-weight: 400;
    margin: 0;
}

.sx-stale-work-modal .sx-stale-work-end-time {
    max-width: 120px;
}

.sx-stale-work-modal .sx-stale-work-section {
    margin-top: 16px;
}

.sx-stale-work-modal .sx-stale-work-section-title {
    color: #222;
    font-weight: 600;
    margin-bottom: 8px;
}

.sx-stale-work-modal .sx-stale-work-interval {
    border-left: 3px solid #d9e1ea;
    margin-bottom: 8px;
    padding: 6px 0 6px 10px;
}

.sx-stale-work-modal .sx-stale-work-interval.sx-is-open {
    border-left-color: var(--color-red-pale);
}

.sx-stale-work-modal .sx-stale-work-interval-time {
    color: #333;
    font-weight: 600;
}

.sx-stale-work-modal .sx-stale-work-task-name,
.sx-stale-work-modal .sx-stale-work-empty,
.sx-stale-work-modal .sx-stale-work-hint {
    color: #777;
}

.sx-stale-work-modal .sx-stale-work-error {
    display: none;
    margin: 0 20px 15px;
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

<div id="sx-stale-work-modal" class="modal fade sx-stale-work-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title sx-stale-work-title">&#1042;&#1099; &#1085;&#1077; &#1079;&#1072;&#1074;&#1077;&#1088;&#1096;&#1080;&#1083;&#1080; &#1088;&#1072;&#1073;&#1086;&#1095;&#1077;&#1077; &#1074;&#1088;&#1077;&#1084;&#1103;</h4>
                <button type="button" class="close sx-stale-work-later" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body sx-stale-work-body">
            </div>
            <div class="alert alert-danger sx-stale-work-error"></div>
            <div class="modal-footer">
                <div class="sx-stale-work-form">
                    <label for="sx-stale-work-end-time">&#1047;&#1072;&#1074;&#1077;&#1088;&#1096;&#1080;&#1090;&#1100; &#1074;</label>
                    <input id="sx-stale-work-end-time" class="form-control sx-stale-work-end-time" type="time" step="60" />
                </div>
                <button type="button" class="btn btn-default sx-stale-work-later" data-dismiss="modal">&#1055;&#1086;&#1079;&#1078;&#1077;</button>
                <button type="button" class="btn btn-primary sx-stale-work-save">&#1047;&#1072;&#1074;&#1077;&#1088;&#1096;&#1080;&#1090;&#1100;</button>
            </div>
        </div>
    </div>
</div>

<div id="sx-idle-work-modal" class="modal fade sx-idle-work-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Завершить рабочее время?</h4>
                <button type="button" class="close sx-idle-work-no" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body sx-idle-work-body">
                <p>Похоже, рабочее время включено, но сейчас нет активной задачи.</p>
                <p>Завершить текущий рабочий период?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default sx-idle-work-no" data-dismiss="modal">Нет</button>
                <button type="button" class="btn btn-primary sx-idle-work-yes">Да, завершить</button>
            </div>
        </div>
    </div>
</div>
