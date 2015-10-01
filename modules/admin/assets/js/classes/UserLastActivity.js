/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.07.2015
 */
(function(sx, $, _)
{
    sx.classes.UserLastActivity = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            this.mergeDefaults({
                'leftTimeInfo': 30, //Время начала информирования пользователя о начале блокировки
                'ajaxLeftTimeInfo': 180, //Время начала выполнения ajax запросов для сверки
                'interval': 5, //Время таймера проверки
                'isGuest': false, //Время таймера проверки
            });

            setInterval(function(){
                self.check();
            }, Number( this.get('interval') * 1000 ) );

        },

        check: function()
        {
            var self = this;

            if (this.getLeftTime() < Number(this.get('leftTimeInfo')) && this.getLeftTime() > 0)
            {
                //TODO: добавить ajax запрос. На обновление состояния текущего объекта. Пользователь мог проявлять активность в сосендней вкладке.
                sx.notify.info('Вы будете заблокированы через ' + this.getLeftTime() + ' секунд, так как давно не проявляете активность.');
            }

            if (this.getLeftTime() < Number(this.get('leftTimeInfo')) && this.getLeftTime() < 0)
            {
                //TODO: добавить ajax запрос. На обновление состояния текущего объекта. Пользователь мог проявлять активность в сосендней вкладке.
                sx.notify.info('Вы заблокированы из за долгой неактивности на сайте');
            }

            if (this.get('isGuest'))
            {
                //TODO: добавить ajax запрос. На обновление состояния текущего объекта. Пользователь мог проявлять активность в сосендней вкладке.
                sx.notify.info('Вам необходимо авторизоваться на сайте');
            }


            if (this.getLeftTime() < Number(this.get('ajaxLeftTimeInfo')))
            {
                var ajaxQuery = sx.ajax.preparePostQuery(this.get('backendGetUser'));

                new sx.classes.AjaxHandlerNoLoader(ajaxQuery);

                ajaxQuery.bind('success', function(e, data)
                {
                    self.merge(data.response.data);
                });

                ajaxQuery.execute();
            }
        },

        /**
         * Время сейчас
         * @returns {number}
         */
        getNowTime: function()
        {
            return Math.floor(Date.now() / 1000);
        },

        /**
         * Время прошедшее
         * @returns {number}
         */
        getPassedTime: function()
        {
            return (this.getNowTime()  - Number(this.get('startTime')));
        },

        /**
         * Время осталось
         * @returns {number}
         */
        getLeftTime: function()
        {
            return (this.getBlockedAfterTime()  - this.getPassedTime());
        },

        /**
         * Заблокировать через
         * @returns {number}
         */
        getBlockedAfterTime: function()
        {
            return Number(this.get('blockedAfterTime'));
        },

    });

})(sx, sx.$, sx._);