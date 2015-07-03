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
            //TODO: добавить какой нибудь хендлер correct time
            var self = this;
            var remind1 = (this.get('timeLeft') - 30) * 1000
            var remind2 = (this.get('timeLeft') - 20) * 1000
            var remind3 = (this.get('timeLeft') - 10) * 1000

            _.delay(function()
            {
                self.remind1();
            }, remind1);

            _.delay(function()
            {
                self.remind2();
            }, remind2);

            _.delay(function()
            {
                self.remind3();
            }, remind3);
        },

        remind1: function()
        {
            sx.notify.info('Вы будете заблокированы через 30 секунд, так как давно не проявляете активность.');
        },

        remind2: function()
        {
            sx.notify.info('Вы будете заблокированы через 20 секунд, так как давно не проявляете активность.');
        },

        remind3: function()
        {
            sx.notify.info('Вы будете заблокированы через 10 секунд, так как давно не проявляете активность.');
        },

        _onDomReady: function()
        {},

        _onWindowReady: function()
        {}
    });

})(sx, sx.$, sx._);