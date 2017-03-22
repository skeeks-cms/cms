/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.04.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.tasks', sx);

    sx.classes.tasks._Manager = sx.classes.Component.extend({

        _init: function()
        {
            this.reset();
        },

        reset: function()
        {
            //Очередь задач
            this.queue      = [];

            //Сейчас этот менеджер выполняется?
            this.isExecuting        = false;

            //Задача которая выполняется в данный момент
            this.executingTask       = null;

            return this;
        },

        /**
        * @returns {*|sx.classes.Task[]}
        */
        getTasks: function()
        {
            return this.get('tasks');
        },

        /**
         * @param tasks
         * @returns {sx.classes.tasks._Manager}
         */
        setTasks: function(tasks)
        {
            this.set('tasks', tasks );
            return this;
        },

        /**
         * @param Task
         * @returns {sx.classes.tasks._Manager}
         */
        addTask: function(Task)
        {
            if (!Task instanceof sx.classes.tasks._Task)
            {
                throw new Error("Некорректная задача");
            }

            var tasks = this.getTasks();
            tasks.push(Task);
            this.setTasks(tasks);
            return this;
        },

        /**
         * Всего задача в менеджере
         * @returns {number|*|Number}
         */
        countTotalTasks: function()
        {
            return Number( _.size(this.getTasks()) );
        },

        /**
         * Задач в очереди (очередь уменьшается с выполнением каждой задачи)
         * @returns {number|*|Number}
         */
        countQuequeTasks: function()
        {
            return Number( _.size(this.queue) );
        },

        /**
         * Загрузка очереди
         * @returns {sx.classes.tasks._Manager}
         */
        _loadQueque: function()
        {
            //console.info('_loadQueque');
            this.queue = this.getTasks();
            return this;
        },

        /**
         * Инициализация следующей задачи
         * @returns {sx.classes.tasks._Manager}
         * @private
         */
        _initNextTask: function()
        {
            //console.info('init next task');

            //Следующая задача, делается выполняемой сайчас.
            this.executingTask = _.first(this.queue);

            //Эта задач убирается из очереди.
            var queue   = this.queue;
            this.queue  = _.rest(queue);

            return this;
        },


        /**
         * Процессинг выполнения задач.
         * @returns {sx.classes.tasks._Manager}
         * @private
         */
        _runProcessing: function()
        {
            //console.info('Task manager _runProcessing');

            var self = this;

            //Не надо ничего запускать, все остановлено
            if (!this.isExecuting)
            {
                return this;
            }

            //В очереди больше нет задач, нужно все остановить.
            if (!this.countQuequeTasks())
            {
                //console.info('В очереди больше нет задач, остановка.');

                this.stop();
                return this;
            }


            //Загруза к менеджер следующей задачи
            this._initNextTask();

            //Если больше
            if (!this.executingTask)
            {
                //console.info('Не определена выполняемая задача, остановка');

                this.stop();
                return this;
            }

            /**
             * Перед выполненияем задачи
             */
            this.executingTask.bind("beforeExecute", function(e, data)
            {
                self.trigger("beforeExecuteTask", {
                    'task' : self.executingTask
                });
            });

            /**
             * После завершения задачи.
             */
            this.executingTask.bind("complete", function(e, data)
            {
                self.trigger("completeTask", {
                    'task' : self.executingTask
                });

                _.delay(function()
                {
                    self._runProcessing();
                }, Number(self.get('delayQueque', 200)) );
            });

            this.executingTask.execute();

            return this;
        },


        /**
         * @returns {sx.classes.tasks._Manager}
         */
        restart: function()
        {
            this.reset();
            return this;
        },

        /**
         * @returns {sx.classes.tasks._Manager}
         */
        start: function()
        {
            //Загрузка очереди
            this._loadQueque();

            //Задачи не найдены
            if (this.countQuequeTasks() == 0)
            {
                this.trigger("error", {
                    'manager' : this,
                    'error' : "Задачи не найдены"
                });

                return this;
            }

            this.trigger("beforeStart", {
                'manager' : this
            });

            this.isExecuting   = true;

            this.trigger("start", {
                'manager' : this
            });

            this._runProcessing();

            return this;
        },

        /**
         * @returns {sx.classes.tasks._Manager}
         */
        stop: function()
        {
            this.trigger("beforeStop", {
                'manager' : this
            });

            this.reset();
            this.set("tasks", []);

            this.trigger("stop", {
                'manager' : this
            });

            return this;
        },
    });

    sx.classes.tasks.Manager = sx.classes.tasks._Manager.extend({});

})(sx, sx.$, sx._);