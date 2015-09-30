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

            setInterval(function(){
                self.check();
            }, Number( this.get('delay', 5000) ) );
        },

        check: function()
        {
            if (this.getLeftTime() < 30 && this.getLeftTime() > 0)
            {
                //TODO: добавить ajax запрос. На обновление состояния текущего объекта. Пользователь мог проявлять активность в сосендней вкладке.
                sx.notify.info('Вы будете заблокированы через ' + this.getLeftTime() + ' секунд, так как давно не проявляете активность.');
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