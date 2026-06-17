/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
(function (sx, $, _) {
    sx.classes.Softphone = sx.classes.Component.extend({

        _init: function () {

            this.states = {
                INIT: 'init',
                REGISTERED: 'registered',
                CALLING: 'calling',
                RINGING: 'ringing',
                TALKING: 'talking',
                ENDED: 'ended'
            }

            this.state = this.states.INIT;
        },

        _setState: function (state) {
            this.state = state;
            this.trigger('stateChange', state);
            console.log('[Softphone] state:', state);
        },

        _onDomReady: function () {
            var self = this;

            const socket = new JsSIP.WebSocketInterface("wss://wss.sipuni.com/api");

            const configuration = {
                sockets: [socket],

                uri: 'sip:070590100002@sipuni.com',
                password: 'GQdBE9D0',


                authorization_user: '070590100002',
                registrar_server: 'sip:sipuni.com',

                display_name: 'Семенов Александр',
                session_timers: false,

                // обязательно для некоторых браузеров
                contact_uri: 'sip:070590100002@sipuni.com;transport=ws'
            };

            JsSIP.debug.enable('JsSIP:*');

            self.ua = new JsSIP.UA(configuration);

            const configuration = Object.assign({}, self.get("sip_config"), {
                sockets: [socket],
                authorization_user: "070590100002",
                registrar_server: 'sip:sipuni.com',
                contact_uri: 'sip:070590100002@sipuni.com;transport=ws'
            });

            JsSIP.debug.enable('JsSIP:*');

            self.ua = new JsSIP.UA(configuration);
            self._bindEvents();
            self.ua.start();

            self.on('stateChange', function (e, state) {
                console.log('Softphone state changed:', state);
            });
        },

        _bindEvents: function () {
            var self = this;

            self.ua.on('registered', function () {
                self._setState(self.states.REGISTERED);
            });

            self.ua.on('registrationFailed', function (e) {
                console.error('SIP registration failed', e.cause);
            });

            self.ua.on('newRTCSession', function (e) {
                self.session = e.session;

                if (e.originator === 'remote') {
                    self._setState(self.states.RINGING);
                    self.onIncoming(e.session);
                }

                e.session.on('ended', function () {
                    self._setState(self.states.ENDED);
                });

                e.session.on('accepted', function () {
                    self._setState(self.states.TALKING);
                });
            });
        },

        onIncoming: function (session) {
            var self = this;
            console.log('Incoming call');

            // пока просто confirm
            if (confirm('Входящий звонок. Принять?')) {
                session.answer({
                    mediaConstraints: { audio: true, video: false }
                });
            } else {
                session.terminate();
            }
        },

        call: function (number) {
            var self = this;

            self._setState(self.states.CALLING);

            self.session = self.ua.call(number, {
                mediaConstraints: { audio: true, video: false }
            });

            self.session.on('failed', function () {
                self._setState(self.states.ENDED);
            });

            self.session.on('accepted', function () {
                self._setState(self.states.TALKING);
            });
        },

        hangup: function () {
            if (this.session) {
                this.session.terminate();
            }
        },

        mute: function () {
            if (this.session) {
                this.session.mute({ audio: true });
            }
        },

        unmute: function () {
            if (this.session) {
                this.session.unmute({ audio: true });
            }
        }


    });
})(sx, sx.$, sx._);