/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.04.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.tasks', sx);

    /**
     * Базовый абстрактный класс
     */
    sx.classes.tasks._ProgressBar = sx.classes.Widget.extend({

        /**
         * @param Manager
         * @param opts
         */
        construct: function(Manager, wrapper, opts)
        {
            if (! (Manager instanceof sx.classes.tasks._Manager))
            {
                throw new Error('Не передан менеджер задач');
            }

            opts = opts || {};
            opts['Manager'] = Manager;

            this.applyParentMethod(sx.classes.Widget, 'construct', [wrapper, opts]);
        },

        _init: function()
        {
            var self = this;

            this.getManager().bind("beforeStart", function(e, data)
            {
                self.setProgressValue(0);
                self.show();
            });

            this.getManager().bind("stop", function(e, data)
            {
                self.hide();
            });

            this.getManager().bind("beforeExecuteTask", function(e, data)
            {
                self.trigger("update", {
                    'ProgressBar' : this,
                    'Task' : data.task
                });
            });

            this.getManager().bind("completeTask", function(e, data)
            {
                self.trigger("update", {
                    'ProgressBar' : this,
                    'Task' : data.task
                });

                self.trigger("updateProgressBar", {
                    'ProgressBar' : this,
                    'Task' : data.task
                });

                self.updateProgress();
            });
        },

        /**
         * Всего задач
         * @returns {number|*|Number}
         */
        total: function()
        {
            return this.getManager().countTotalTasks();
        },

        /**
         * Осталось в очереди
         * @returns {number|*|Number}
         */
        queque: function()
        {
            return this.getManager().countQuequeTasks();
        },

        /**
         * Столько выполнено
         * @returns {number}
         */
        executed: function()
        {
            return (this.total() - this.queque());
        },

        /**
         * @returns {number}
         */
        getExecutedPtc: function()
        {
            //return round(100 * this.executed() / this.total());
            var n = Number(100 * this.executed() / this.total());
            return n.toFixed(1);
        },

        /**
         * @returns {number}
         */
        getQuequePtc: function()
        {
            var n = Number(100 * this.queque() / this.total());
            return n.toFixed(1);
        },

        /**
         * @returns {sx.classes.tasks._ProgressBar}
         */
        show: function()
        {
            var self = this;

            this.onDomReady(function()
            {
                self.getWrapper().show();
            });

            return this;
        },

        /**
         * @returns {sx.classes.tasks._ProgressBar}
         */
        hide: function()
        {
            var self = this;

            this.onDomReady(function()
            {
                self.getWrapper().hide();
            });

            return this;
        },

        /**
         *
         * @returns {sx.classes.tasks._Manager}
         */
        getManager: function()
        {
            return this.get("Manager");
        },

        /**
         * @param pct
         */
        updateProgress: function()
        {
            this.setProgressValue(this.getExecutedPtc());
            return this;
        },

        /**
         * @param pct
         */
        setProgressValue: function(ptc)
        {
            console.log('value: ' + ptc);
            $('.progress-bar', this.getWrapper()).css('width', Number(ptc) + '%');
            return this;
        }
    });

    sx.classes.tasks.ProgressBar = sx.classes.tasks._ProgressBar.extend({});

})(sx, sx.$, sx._);