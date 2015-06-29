/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
(function(sx, $, _)
{
    sx.classes.Installer = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            this.TaskManager = new sx.classes.tasks.Manager({
                'tasks' : [],
                'delayQueque' : 500
            });

            this.ProgressBar = new sx.classes.InstallerProgressBar(this.TaskManager, "#sx-progress-tasks");

            this.TaskManager.bind('start', function()
            {
                $('#' + self.get('id')).show();
                sx.App.Menu.block();
            });

            this.TaskManager.bind('stop', function()
            {
                sx.App.Menu.unblock();
                $('#' + self.get('id')).hide();
            });

            this.checkAccess();
        },

        /**
         * @returns {boolean}
         */
        checkAccess: function()
        {
            if (!this.get('canSsh'))
            {
                sx.notify.error("У вас нет доступа к ssh консоли. Пожалуйста, обратитесь к администратору, и запросите у него доступ к разделу 'Инструменты -> Ssh console'");
                return false;
            }

            return true;
        },

        update: function()
        {
            if (!this.checkAccess())
            {
                return this;
            }

            var tasks = [];

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подготовка к обновлению',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка системы, окружения',
                'delay':2000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка совместимости',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Запуска ssh консоли',
                'delay':1000,
                'callback':function()
                {
                    var jSshConsole = $('#sx-ssh-console-wrapper');
                    jSshConsole.show();
                }
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/composer/revert-modified-files',
                'name':'Откат модификаций ядра',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подключение к серверу обновлений',
                'delay':2000
            }));


            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/update',
                'name':'Загрузка пакетов и их обновление (длиться около 1 минуты)',
                'delay': 500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/dump-list',
                'name':'Создание резервной копии базы данных',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/utils/generate-modules-config-file',
                'name':'Генерация файла со списком модулей',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/apply-migrations',
                'name':'Установка всех миграций базы данных',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/utils/clear-runtimes',
                'name':'Чистка временных диррикторий',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/db-refresh',
                'name':'Сброс кэша стрктуры базы данных',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/rbac/init',
                'name':'Обновление привилегий',
            }));


            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка обновленных решений',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Завершение процесса обновления',
                'delay':3000,
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Готово',
                'delay':200,
                'callback':function()
                {
                    sx.notify.success('Полное обновление системы завершено');
                }
            }));


            this.TaskManager.setTasks(tasks);
            this.TaskManager.start();
        },

        remove: function(packageName)
        {
            if (!this.checkAccess())
            {
                return this;
            }

            var tasks = [];

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подготовка к удалению пакета',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка системы, окружения',
                'delay':2000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Запуска ssh консоли',
                'delay':1000,
                'callback':function()
                {
                    var jSshConsole = $('#sx-ssh-console-wrapper');
                    jSshConsole.show();
                }
            }));


            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/composer/revert-modified-files',
                'name':'Откат модификаций ядра',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/backup/db-execute',
                'name':'Создание резервной копии базы данных',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/update/remove ' + packageName,
                'name':'Удаление пакета и его зависимостей (длиться около 1 минуты)',
                'delay': 500
            }));



                tasks.push(new sx.classes.InstallerTaskConsole({
                    'cmd':'php yii cms/utils/generate-modules-config-file',
                    'name':'Генерация файла со списком модулей',
                }));

                tasks.push(new sx.classes.InstallerTaskConsole({
                    'cmd':'php yii cms/db/apply-migrations',
                    'name':'Установка всех миграций базы данных',
                }));

                tasks.push(new sx.classes.InstallerTaskConsole({
                    'cmd':'php yii cms/utils/clear-runtimes',
                    'name':'Чистка временных диррикторий',
                }));

                tasks.push(new sx.classes.InstallerTaskConsole({
                    'cmd':'php yii cms/db/db-refresh',
                    'name':'Сброс кэша стрктуры базы данных',
                }));

                tasks.push(new sx.classes.InstallerTaskConsole({
                    'cmd':'php yii cms/rbac/init',
                    'name':'Обновление привилегий',
                }));


            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Тестирование системы после удаления',
                'delay':3000,
            }));
            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Завершение процесса удаления',
                'delay':3000,
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Готово',
                'delay':200,
                'callback':function()
                {
                    sx.notify.success('Удаление пакета завершено');
                }
            }));

            this.TaskManager.setTasks(tasks);
            this.TaskManager.start();
        },


        install: function(packageName)
        {
            if (!this.checkAccess())
            {
                return this;
            }

            var tasks = [];

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подготовка к установке пакета',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка системы, окружения',
                'delay':2000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка совместимости пакета',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Запуска ssh консоли',
                'delay':1000,
                'callback':function()
                {
                    var jSshConsole = $('#sx-ssh-console-wrapper');
                    jSshConsole.show();
                }
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/composer/revert-modified-files',
                'name':'Откат модификаций ядра',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подключение к серверу обновлений',
                'delay':2000
            }));


            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/update/install ' + packageName,
                'name':'Скачивание пакета и его установка (длиться около 1 минуты)',
                'delay': 500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/dump-list',
                'name':'Создание резервной копии базы данных',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/utils/generate-modules-config-file',
                'name':'Генерация файла со списком модулей',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/apply-migrations',
                'name':'Установка всех миграций базы данных',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/utils/clear-runtimes',
                'name':'Чистка временных диррикторий',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/db-refresh',
                'name':'Сброс кэша стрктуры базы данных',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/rbac/init',
                'name':'Обновление привилегий',
            }));


            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка установленного решения',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Завершение процесса установки',
                'delay':3000,
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Готово',
                'delay':200,
                'callback':function()
                {
                    sx.notify.success('Установка пакета завершена');
                }
            }));


            this.TaskManager.setTasks(tasks);
            this.TaskManager.start();
        },

    });
})(sx, sx.$, sx._);