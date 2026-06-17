<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsTask */
$this->registerJsFile("https://cdn.jsdelivr.net/npm/jssip@3.10.0/dist/jssip.min.js", [
    'depends' => [\yii\web\JqueryAsset::class]
]);
$this->registerJs(<<<JS
var SIP_CONFIG = {
  "uri": "sip:1001@sip.sipuni.com",
  "password": "8a4a8f5ad7f0df7a1fd4e4ff6cd003d8",
  "ws": "wss://wss.sipuni.com/api",
  "displayName": "CRM Agent"
}

window.Softphone = (function ($) {

    let ua = null;
    let session = null;

    function init(config) {

        const socket = new JsSIP.WebSocketInterface(config.ws);

        ua = new JsSIP.UA({
            sockets: [socket],
            uri: config.uri,
            password: config.password,
            display_name: config.displayName,
            session_timers: false
        });

        ua.on('connected', () => updateStatus('online'));
        ua.on('disconnected', () => updateStatus('offline'));

        ua.on('newRTCSession', function (e) {
            session = e.session;

            if (session.direction === 'incoming') {
                incomingCall(session);
            }

            session.on('ended', endCall);
            session.on('failed', endCall);
            session.on('confirmed', () => updateStatus('talking'));
        });

        ua.start();
    }

    function call(phone) {
        session = ua.call(phone, {
            mediaConstraints: { audio: true, video: false }
        });
        updateStatus('calling');
    }

    function answer() {
        if (session) {
            session.answer({ mediaConstraints: { audio: true, video: false } });
            updateStatus('talking');
        }
    }

    function hangup() {
        if (session) session.terminate();
    }

    function incomingCall(sess) {
        $('#sp-number').text(sess.remote_identity.uri.user);
        $('#sp-answer').prop('disabled', false);
        $('#sp-hangup').prop('disabled', false);
        updateStatus('incoming');
    }

    function endCall() {
        session = null;
        $('#sp-answer').prop('disabled', true);
        $('#sp-hangup').prop('disabled', true);
        updateStatus('ready');
    }

    function updateStatus(text) {
        $('#sp-status').text(text);
    }

    return {
        init,
        call,
        answer,
        hangup
    };

})(jQuery);

$(function () {

    // 1. инициализация
    $(window).on('load', function () {
        if (typeof JsSIP === 'undefined') {
            console.error('JsSIP not loaded');
            return;
        }
        Softphone.init(window.SIP_CONFIG);
    });

    // 2. кнопки
    $('#sp-call').on('click', function () {
        Softphone.call($('#sp-input').val());
    });

    $('#sp-answer').on('click', Softphone.answer);
    $('#sp-hangup').on('click', Softphone.hangup);

});

JS
)
?>

<div id="softphone">
    <div class="display" id="sp-number">Готов</div>

    <input type="text" id="sp-input" placeholder="+79991234567">

    <div class="buttons">
        <button id="sp-call">📞</button>
        <button id="sp-answer" disabled>✅</button>
        <button id="sp-hangup" disabled>❌</button>
    </div>

    <div class="status" id="sp-status">offline</div>
</div>


<button onclick="Softphone.call('+79037222873')">
    Позвонить
</button>