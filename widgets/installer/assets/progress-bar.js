/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
(function(sx, $, _)
{
    sx.classes.InstallerProgressBar = sx.classes.tasks.ProgressBar.extend({

        _init: function()
        {
            var self = this;

            this.applyParentMethod(sx.classes.tasks.ProgressBar, '_init', []);

            this.bind('update', function(e, data)
            {
                $(".sx-executing-task-name", self.getWrapper()).empty().append(data.Task.get('name'));
            });

            this.bind('updateProgressBar', function(e, data)
            {
                $(".sx-executing-ptc", self.getWrapper()).empty().append(self.getExecutedPtc());
            });
        }

    });

})(sx, sx.$, sx._);