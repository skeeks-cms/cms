/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
(function(sx, $, _)
{
    sx.classes.InstallerTaskAjax = sx.classes.tasks.AjaxTask.extend({

    });

    sx.classes.InstallerTaskClean = sx.classes.tasks.Task.extend({
        execute: function()
        {
            var self = this;

            this.trigger("beforeExecute", {
                'task' : this
            });

            var result = {'message': 'Задача выполнена'};

            if (this.get('callback'))
            {
                var callback = this.get('callback');
                callback();
            }

            _.delay(function()
            {
                self.trigger("complete", {
                    'task'      : self,
                    'result'    : result
                });
            }, this.get('delay', 2000));
        }
    });

    sx.classes.InstallerTaskConsole = sx.classes.tasks.Task.extend({
        execute: function()
        {
            var self = this;

            this.trigger("beforeExecute", {
                'task' : this
            });

            sx.SshConsole.unbind('success');
            sx.SshConsole.unbind('error');

            _.delay(function()
            {
                sx.SshConsole.execute(self.get('cmd'));

            }, this.get('delay', 1000));


            sx.SshConsole.bind('success', function()
            {
                _.delay(function()
                {
                    self.trigger("complete", {
                        'task'      : self,
                        'result'    : {}
                    });
                }, self.get('delay', 1000));
            });

            sx.SshConsole.bind('error', function()
            {
                _.delay(function()
                {
                    self.trigger("complete", {
                        'task'      : self,
                        'result'    : {}
                    });
                }, self.get('delay', 1000));
            });

        }
    });
})(sx, sx.$, sx._);