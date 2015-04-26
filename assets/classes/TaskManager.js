/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.04.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.tasks', sx);

    sx.classes.tasks._Task = sx.classes.Component.extend({

    });
    sx.classes.tasks.Task = sx.classes.tasks._Task.extend({});

    sx.classes.tasks._TaskManagerProcessing = sx.classes.Component.extend({

        construct: function (TaskManager, opts)
        {
            var self = this;

            opts = opts || {};
            opts.taskManager = TaskManager;

            this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
        },


        start: function()
        {
            this.getProcessing().start();
        },

        stop: function()
        {
            this.getProcessing().stop();
            this.processing     = null;
        },

        pause: function()
        {
            this.getProcessing().pause();
        }

    });
    sx.classes.tasks.TaskManagerProcessing = sx.classes.tasks._TaskManagerProcessing.extend({});

    sx.classes.tasks._TaskManager = sx.classes.Component.extend({

        _init: function()
        {
            this.processing = false;
        },

        /**
        * @returns {*|sx.classes.Task[]}
        */
        getTasks: function()
        {
            return Array(this.get('tasks'));
        },

        setTasks: function(tasks)
        {
            this.set('tasks', Array(tasks) );
            return this;
        },

        /**
         * @returns {Function}
         */
        getProcessing: function()
        {
            if (!this.processing)
            {
                this.processing = new sx.classes.tasks.TaskManagerProcessing(this);
            }

            return this.processing;
        },



        start: function()
        {
            this.getProcessing().start();
        },

        stop: function()
        {
            this.getProcessing().stop();
            this.processing     = null;
        },

        pause: function()
        {
            this.getProcessing().pause();
        }
    });
    sx.classes.tasks.TaskManager = sx.classes.tasks._TaskManager.extend({});


})(sx, sx.$, sx._);