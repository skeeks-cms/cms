/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

(function (sx, $, _) {
    /**
     *
     */
    sx.createNamespace('classes', sx);
    /**
     * Преобразование ссылок на клиенте
     */
    sx.classes.LinkActivation = sx.classes.Component.extend({

        /**
         * @param Selector
         * @param opts
         */
        construct: function (Selector, opts) {
            opts = opts || {};
            this.Selector = Selector || false;

            this.applyParentMethod(sx.classes.Component, 'construct', [opts]);
        },

        _onDomReady: function () {
            var self = this;

            if (this.Selector === false) {
                return false;
            }

            this.jQuerySelector = $(this.Selector);

            if ($.pjax) {
                $(document).on('pjax:complete', function (e) {
                    self._replace($(self.Selector, $(e.target)))
                    return true;
                });
            }

            this._replace(this.jQuerySelector);
        },

        _replace: function (jQuerySelector) {
            var self = this;

            jQuerySelector.each(function () {
                var jQueryTextWrapper = $(this);


                $("a[href^='http']", jQueryTextWrapper).wrap("<span class='sx-link'></span>");
                /*$("a[href^='https']", jQueryTextWrapper).wrapAll("<span class='sx-link'></span>");*/
                $("a[href^='ftp']", jQueryTextWrapper).wrap("<span class='sx-link'></span>");

                $(".sx-link", jQueryTextWrapper).each(function () {
                    var textLink = ("a", $(this)).text();
                    $(this).empty().append(textLink + " ");
                });

                var text = jQueryTextWrapper.html();
                //console.log(text);


                text = text.replace(new RegExp("<br>", 'g'), " <br> ");

                //var text = jQueryTextWrapper.html();
                /*var replaced = text.replace(/([^\"=]{2}|^)((https?|ftp):\/\/\S+[^\s.,> )\];'\"!?])/, '$1<a href="$2" target="_blank" data-pjax="0">$2</a>');*/
                var replaced = text.replace(self.getRegex(), '<a href="$&" target="_blank" data-pjax="0">$&</a>');
                jQueryTextWrapper.empty().append(replaced);
            });
        },

        /**
         * @returns {RegExp}
         */
        getRegex: function () {
            return new RegExp(
                // protocol identifier
                "(?:(?:https?|ftp)://)" +
                // user:pass authentication
                "(?:\S+(?::\S*)?@)?" +
                //"(?:\\S+(?::\\S*)?@)?" +
                "(?:" +
                // IP address exclusion
                // private & local networks
                "(?!(?:10|127)(?:\.\d{1,3}){3})" +
                "(?!(?:169\\.254|192\.168)(?:\.\d{1,3}){2})" +
                "(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})" +
                // IP address dotted notation octets
                // excludes loopback network 0.0.0.0
                // excludes reserved space >= 224.0.0.0
                // excludes network & broacast addresses
                // (first & last IP address of each class)
                "(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])" +
                "(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}" +
                "(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))" +
                "|" +
                // host name
                "(?:(?:[a-z0-9]-*)*[a-z0-9]+)" +
                //"(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)" +
                // domain name
                "(?:\.(?:[a-z0-9]-*)*[a-z0-9]+)*" + //~_
                //"(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*" + //Берутся пробелы в том числе


                // TLD identifier
                "(?:\.(?:[a-z0-9]{2,}))" +
                "(?:\.(?:[a-z0-9/?&_%=~-]{2,}))" + //http://sobirayka.ru.vps210.s7.h.skeeks.com/~sx/admin/admin-auth?_sx%5Bref%5D=%2F~sx

                //"(?:\.(?:[a-z0-9/?&;_%=~-]{2,}))" + //http://alekseevamv.ru.vps211.s7.h.skeeks.com/~sx/cms/admin-settings?component=&amp;component=v3project%5Cthemes%5Cmega%5CThemeMegaSettings
                //"(?:\.(?:[/]{2,}))" +
                // sorry, ignore TLD ending with dot
                //"\.?" +
                ")" +
                // port number
                "(?::\d{2,5})?"
                // resource path, excluding a trailing punctuation mark
                //+ "(?:[/?#](?:\S*[^\s!\"'()*,-.:;<>?\[\]_`{|}~]|))?"
                , "gi"
            );
        }

    });
})(sx, sx.$, sx._);