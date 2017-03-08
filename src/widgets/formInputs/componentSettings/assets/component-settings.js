/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
(function(sx, $, _)
{
    sx.classes.ComponentSettingsWidget = sx.classes.Component.extend({

        getjWrapper: function()
        {
            return $("#" + this.get('id'));
        },

        getjBtnEdit: function()
        {
            return $(".sx-btn-edit", this.getjWrapper());
        },

        getjComponentSelect: function()
        {
            return $("#" + this.get('componentSelectId'));
        },
        getjComponentSettings: function()
        {
            return $("#" + this.get('componentSettingsId'));
        },

        _onDomReady: function()
        {
            var self = this;

            this.getjComponentSelect().on("change", function()
            {
                self.currentComponent = $(this).val();
                self.update();
            });

            this.getjBtnEdit().on('click', function()
            {
                self.goEdit();
                return false;
            });

            this.currentComponent = this.getjComponentSelect().val();
            self.update();
        },

        /**
         * Обновление текущего состояния
         * @returns {sx.classes.ComponentSettingsWidget}
         */
        update: function()
        {
            var self = this;

            if (!self.currentComponent)
            {
                this.getjBtnEdit().hide();
            } else
            {
                this.getjBtnEdit().show();
            }
            return this;
        },

        save: function(dataStringB64)
        {
            var old = this.getjComponentSettings().val();
            this.getjComponentSettings().val(dataStringB64);
            var newVal = this.getjComponentSettings().val();

            if (newVal != old)
            {
                console.log('changed');
            }
            this.windowObject.close();
        },

        goEdit: function()
        {
            var url = this.get('backend');
                url = url + '?';
                url = url + "&component=" + this.currentComponent;
                url = url + "&settings="  + this.getjComponentSettings().val();
                url = url + "&callbackComonentId="  + this.get('id');

            this.windowObject = new sx.classes.Window(url);
            this.windowObject.open();
        },
    });
})(sx, sx.$, sx._);