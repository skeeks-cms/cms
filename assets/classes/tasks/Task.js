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
     * Задача которую необходимо выполнить.
     */
    sx.classes.tasks._Task = sx.classes.Component.extend({

        execute: function()
        {
            //throw new Error("Это асбтрактный класс.");

            this.trigger("beforeExecute", {
                'task' : this
            });

            var result = {'message': 'Задача выполнена'};

            this.trigger("complete", {
                'task'      : this,
                'result'    : result
            });
        }
    });

    sx.classes.tasks.Task = sx.classes.tasks._Task.extend({});


    /*_init: function()
    {
        this.attachedManagers = {};
    },

    *//**
     * @param Manager
     * @returns {boolean}
     *//*
    isAttachedToManager: function(Manager)
    {
        if (! Manager instanceof sx.classes.tasks._Manager)
        {
            throw new Error("Невозможно привязать эту задачу к этому менеджеру");
        }

        var id = Manager.getId();
        if (typeof this.attachedManagers[id] == "undefined")
        {
            return false;
        } else
        {
            return true;
        }
    },

    *//**
     * @param Manager
     * @returns {sx.classes.tasks._Task}
     *//*
    attachToManager: function(Manager)
    {
        if (! Manager instanceof sx.classes.tasks._Manager)
        {
            throw new Error("Невозможно привязать эту задачу к этому менеджеру");
        }

        this.attachedManagers[Manager.getId()] = Manager;
        return this;
    },*/
})(sx, sx.$, sx._);