(function (sx, $, _) {

    sx.classes.Telephony = sx.classes.Component.extend({

        provider_call_id: null,
        ignoreCurrentCall: false,
        ignoredCallIds: {},
        pollingTimer: null,
        pollingInterval: 2000,

        _onDomReady: function () {
            var self = this;

            this._cacheUI();
            this._bindUI();
            this._startPolling();

            $("body").on("click", ".sx-telephony-btn", function() {
                self.call($(this).data("value"));
                return false;
            });
        },

        /* ================= UI ================= */

        _cacheUI: function () {
            this.$panel  = $('#telephony-call-panel');
            this.$title  = this.$panel.find('.telephony-title');
            this.$phone  = this.$panel.find('.telephony-phone');
            this.$status = this.$panel.find('.telephony-status');
            this.$cancel = this.$panel.find('.telephony-cancel');

            this.$company = this.$panel.find('.telephony-company');
            this.$companyImg  = this.$panel.find('.telephony-company-img');
            this.$companyName = this.$panel.find('.telephony-company-name');

            this.$client = this.$panel.find('.telephony-client');
            this.$clientImg  = this.$panel.find('.telephony-client-img');
            this.$clientName = this.$panel.find('.telephony-client-name');
        },

        _bindUI: function () {
            var self = this;

            this.$panel.on('click', '.telephony-close', function () {
                self._dismissCurrentCall();
            });

            this.$panel.on('click', '.telephony-cancel', function () {
                self.cancel();
            });
        },

        _showPanel: function () {
            this.$panel.show();
        },

        _hidePanel: function () {
            this.$panel.hide();
        },

        /* ================= OUTGOING ================= */

        call: function (phone) {
            var self = this;

            self.ignoreCurrentCall = false;
            self.provider_call_id = null;

            self.$title.text('Исходящий звонок');
            self.$phone.text(phone);
            self.$status.text('Набор номера…');

            self._clearEntities();
            self._showPanel();

            self._post(this.get('urls').call, { phone: phone })
                .done(function (res) {
                    if (res.success && res.provider_call_id) {
                        self.provider_call_id = res.provider_call_id;
                    } else {
                        self.$status.text(res.message || 'Ошибка вызова');
                    }
                });
        },

        cancel: function () {
            var self = this;

            if (!self.provider_call_id) {
                self._hidePanel();
                return;
            }

            self.ignoreCurrentCall = true;
            self.$status.text('Отмена вызова…');
            self.ignoredCallIds[self.provider_call_id] = true;

            self._post(this.get('urls').cancel, {
                callId: self.provider_call_id
            }).always(function () {
                self.provider_call_id = null;
                self._hidePanel();
            });
        },

        _dismissCurrentCall: function () {
            if (this.provider_call_id) {
                this.ignoredCallIds[this.provider_call_id] = true;
            }

            this.provider_call_id = null;
            this.ignoreCurrentCall = true;
            this._hidePanel();
        },

        /* ================= POLLING ================= */

        _startPolling: function () {
            var self = this;
            if (self.pollingTimer) return;

            self.pollingTimer = setInterval(function () {
                self._poll();
            }, self.pollingInterval);
        },

        _poll: function () {
            var self = this;

            // 1️⃣ ВСЕГДА сначала incoming
            self._get(self.get('urls').incoming)
                .done(function (res) {

                    if (res.success && res.hasCall && res.call) {
                        if (self._isIgnoredCall(res.call.provider_call_id)) {
                            return;
                        }

                        // если появился новый звонок — подхватываем его
                        if (!self.provider_call_id) {
                            self.provider_call_id = res.call.provider_call_id;
                        }

                        self._renderCall(res.call);
                        return;
                    }

                    // 2️⃣ Если знаем provider_call_id — обновляем статус
                    if (self.provider_call_id) {
                        self._pollStatus();
                    }
                });
        },

        _pollStatus: function () {
            var self = this;

            self._get(self.get('urls').status, {
                callId: self.provider_call_id
            }).done(function (res) {

                if (!res.success || !res.hasCall) {
                    self.provider_call_id = null;
                    return;
                }

                self._renderCall(res.call);

                if (res.call.is_finished) {
                    setTimeout(function () {
                        self.provider_call_id = null;
                        self._hidePanel();
                    }, 2000);
                }
            });
        },

        _pollIncoming: function () {
            var self = this;

            self._get(self.get('urls').incoming)
                .done(function (res) {

                    if (!res.success || !res.hasCall) {
                        return;
                    }

                    if (self.ignoreCurrentCall || self._isIgnoredCall(res.call.provider_call_id)) {
                        return;
                    }

                    if (res.call.provider_call_id) {
                        self.provider_call_id = res.call.provider_call_id;
                    }

                    self._renderCall(res.call);
                });
        },

        /* ================= RENDER ================= */

        _renderCall: function (call) {
            this.$title.text(call.direction === 'in' ? 'Входящий звонок' : 'Исходящий звонок');
            this.$phone.text(call.client_phone || '');
            this.$status.text(call.status_text || '');

            this._renderCompany(call.company);
            this._renderClient(call.client);

            this._showPanel();

            if (call.is_finished) {
                this.$cancel.hide();
            } else {
                this.$cancel.show();
            }
        },

        _renderCompany: function (company) {
            if (!company || !company.id) {
                this.$company.hide();
                return;
            }

            this.$companyName.text(company.name);
            company.image_src
                ? this.$companyImg.attr('src', company.image_src).show()
                : this.$companyImg.hide();

            this.$company.show();
        },

        _renderClient: function (client) {
            if (!client || !client.id) {
                this.$client.hide();
                return;
            }

            this.$clientName.text(client.name);
            client.image_src
                ? this.$clientImg.attr('src', client.image_src).show()
                : this.$clientImg.hide();

            this.$client.show();
        },

        _isIgnoredCall: function (providerCallId) {
            return providerCallId && this.ignoredCallIds[providerCallId];
        },

        _clearEntities: function () {
            this.$company.hide();
            this.$client.hide();
        },

        /* ================= AJAX ================= */

        _get: function (url, data) {
            return $.getJSON(url, data || {});
        },

        _post: function (url, data) {
            return $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: data || {}
            });
        }

    });

})(sx, sx.$, sx._);
