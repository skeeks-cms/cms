/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 04.03.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.files', sx);
    sx.createNamespace('classes.files.sources', sx);



        /**
         * Источники загрузки файлов
         */


    /**
    * Источник получения файлов
     *
    * @type {*|void|Function}
    * @private
    */
    sx.classes.files._Source = sx.classes.Component.extend({

        /**
         * @param Manager
         * @param opts
         */
        construct: function(Manager, opts)
        {
            var self = this;

            if (! (Manager instanceof sx.classes.files._Manager))
            {
                throw new Error('Не передан менеджер загрузки');
            }

            opts = opts || {};
            opts['manager'] = Manager;
            opts['id']      = "sx-" + self.strRand();

            //В процессе выполнения?
            this.inProcess  = false;
            this.queue      = 0;

            this.applyParentMethod(sx.classes.Component, 'construct', [opts]);
        },

        _init: function()
        {
            var self = this;

            this.allFiles = 0;
            this.elseFiles = 0;

            this.bind("startUpload", function(e, data)
            {
                self.allFiles = Number(data.queueLength);
                self.elseFiles = Number(data.queueLength);
            });

            this.bind("completeUploadFile", function(e, data)
            {
                self.elseFiles = self.elseFiles - 1;
                var uploadedFiles = (self.allFiles - self.elseFiles);
                var pct = (uploadedFiles * 100)/self.allFiles;

                self.triggerOnProgress({
                    'pct': pct,
                    'elseFiles': self.elseFiles,
                    'allFiles': self.allFiles,
                    'uploadedFiles': uploadedFiles,
                });
            });

            this._initManagerEvents();
            this._afterInit();
        },

        _initManagerEvents: function()
        {
            var self = this;

            this.bind("error", function(e, message)
            {
                self.getManager().trigger("error", message);
            });

            this.bind("completeUpload", function(e, data)
            {
                self.getManager().trigger('completeUpload', data);
            });

            this.bind("startUpload", function(e, data)
            {
                //queueLength
                self.getManager().trigger('startUpload', data);
            });

            this.bind("startUploadFile", function(e, data)
            {
                //queueLength
                self.getManager().trigger('startUploadFile', data);
            });

            this.bind("completeUploadFile", function(e, data)
            {
                //queueLength
                self.getManager().trigger('completeUploadFile', data);
            });

            this.bind("onProgressFile", function(e, data)
            {
                //queueLength
                self.getManager().trigger('onProgressFile', data);
            });

            this.bind("onProgress", function(e, data)
            {
                //queueLength
                self.getManager().trigger('onProgress', data);
            });
        },

        _afterInit: function()
        {},

        /**
         * Рандомная строка
         * @returns {string}
         */
        strRand: function()
        {
            var result       = '';
            var words        = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
            var max_position = words.length - 1;
                for( i = 0; i < 6; ++i ) {
                    position = Math.floor ( Math.random() * max_position );
                    result = result + words.substring(position, position + 1);
                }
            return result;
        },

        /**
         * Начало выполнения загрузки файлов
         *
         * @param data
         * @returns {sx.classes.files.sources.Base}
         */
        triggerStartUpload: function(data)
        {
            this.trigger("startUpload", data);
            return this;
        },

        

        /**
         * Все файлы загружены процесс остановлен
         *
         * @param data
         * @returns {sx.classes.files._Source}
         */
        triggerCompleteUpload: function(data)
        {
            this.trigger("completeUpload", data);
            return this;
        },

        /**
         * Начало загрузки файла
         *
         * @param data
         * @returns {sx.classes.files.sources.Base}
         */
        triggerStartUploadFile: function(data)
        {
            this.trigger("startUploadFile", data);
            return this;
        },

        /**
         * завершение загрузки файла
         *
         * @param data
         * @returns {sx.classes.files.sources.Base}
         */
        triggerCompleteUploadFile: function(data)
        {
            this.trigger("completeUploadFile", data);
            return this;
        },

        /**
         * @param data
         * @returns {sx.classes.files._Source}
         */
        triggerOnProgress: function(data)
        {
            this.trigger("onProgress", data);
            return this;
        },

        /**
         * Процесс загрузки файла
         *
         * @param data
         * @returns {sx.classes.files.sources.Base}
         */
        triggerOnProgressFile: function(data)
        {
            this.trigger("onProgressFile", data);
            return this;
        },

        /**
         * Произошла ошибка
         *
         * @param msg
         * @returns {sx.classes.files.sources.Base}
         */
        triggerError: function(data)
        {
            this.trigger("error", data);
            return this;
        },

        /**
         *
         * @returns {sx.classes.files._Manager}
         */
        getManager: function()
        {
            return this.get("manager");
        },

        /**
         * @param str
         * @returns {string}
         */
        safeName: function(str)
        {
            return String( str )
               .replace( /&/g, '&amp;' )
               .replace( /"/g, '&quot;' )
               .replace( /'/g, '&#39;' )
               .replace( /</g, '&lt;' )
               .replace( />/g, '&gt;' );
        }
    });


    /**
     * Удаленная загрузка файлов
     */
    sx.classes.files.sources.RemoteUpload = sx.classes.files._Source.extend({

        run: function()
        {
            var self = this;
            //По клику на кнопку, загрузить по http, рисуем textarea, предлагаем ввести пользователю ссылки на изображения, которые хотим скачать, резделив их через запятую или с новой строки.
            //По нажатию кнопки начало загрузки.
            sx.prompt("Введите URL файла начиная с http://", {
                'yes': function (e, result)
                {
                    self._processing(result);
                }
            });
        },

        _processing: function(link)
        {
            var self = this;
            //1) считаем сколько всего пользователь указал ссылок (это делается на js)
            this.httpLinks = [link];

            self.queue = _.size(this.httpLinks);   //В очереди к загрузки осталось столько то файлов
            self.inProcess = true;                     //Загрузчик в работе

            self.triggerStartUpload({
                'queueLength': _.size(this.httpLinks) //сообщаем сколько файлов к загрузке всего
            });

            //Берем каждую, и обрабатываем по очереди.
            _.each(this.httpLinks, function (link, key) {
                //Кидаем событие, начало работы с файлом
                self.triggerStartUploadFile({
                    'name': link,      //ссылка к загрузке
                    'additional': {}  //дополнительная информация
                });

                var ajaxData = _.extend(self.getManager().getCommonData(), {
                    'link': link
                });

                var ajax = sx.ajax.preparePostQuery(self.get('url'), ajaxData);

                ajax.onComplete(function (e, data)
                {
                    self.triggerCompleteUploadFile({
                        'response': data.jqXHR.responseJSON
                    });

                    self.queue = self.queue - 1;

                    if (self.queue == 0)
                    {
                        self.inProcess = false;
                        self.triggerCompleteUpload({});
                    }
                });

                ajax.execute();
            });
        }
    });

    /**
     *
     */
    sx.classes.files.sources.FileManagerUpload = sx.classes.files._Source.extend({

        _init: function ()
        {}
    });

    /**
     * Мультизагрузка файлов Simpleajaxuploader
     * @type {*|Function|void}
     */
    sx.classes.files.sources.SimpleUpload = sx.classes.files._Source.extend({

        _afterInit: function()
        {
            var self = this;

            this.uploaderObj = null;

            this.getManager().bind("changeData", function(e, data)
            {
                if (self.uploaderObj)
                {
                    self.uploaderObj.setData(self.getManager().getCommonData());
                }
            });

            if (self.uploaderObj)
            {
                self.uploaderObj.setData(self.getManager().getCommonData());
            }
        },

        _onDomReady: function()
        {
            /*this.jControllButton = $("<div>", {
                'id' : this.get('id'),
                'style':'display: none;'
            }).append("test").appendTo($("body"));*/
        },

        _onWindowReady: function()
        {
            var self        = this;

            this.buttons    = this.get('buttons', [
                document.getElementById('source-simpleUpload')
            ]);

            this.uploaderObj = new ss.SimpleUpload(_.extend({
                queue: true,
                debug: false,
                maxUploads: 1,
                multiple: true,
                button: this.buttons,
                onExtError: function(filename, extension)
                {
                    self.triggerError({
                        'message' : filename + " тип файла не разрешен к загрузке"
                    });
                },
                onSizeError: function(filename, fileSize)
                {
                    self.triggerError({
                        'message' : filename + " слишком большой, допустимо не более " + Number(self.get("options").maxSize) + " Kb"
                    });
                },

                onProgress: function(pct)
                {
                    self.triggerOnProgressFile({
                        'pct': pct
                    });
                },

                onError: function( filename, type, status, statusText, response, uploadBtn )
                {
                    if (status == 413)
                    {
                        self.triggerError({
                            'message' : 'Не удалось загрузить файл. Слишком большой.'
                        });
                    } else
                    {
                        self.triggerError({
                            'message' : 'Ошибка загрузки файла. Код ошибки: ' + status + " " + statusText
                        });
                    }

                    self.triggerCompleteUploadFile({
                        'name' : filename,
                        'response' : response,
                    });

                    self.queue      = this._queue.length;

                    if (this._queue.length == 0)
                    {
                        self.inProcess  = false;
                        self.triggerCompleteUpload({});
                    }

                },

                onSubmit: function(filename, ext)
                {
                    //Если еще не в процессе выполнения
                    if (self.inProcess === false)
                    {
                        self.queue      = this._queue.length;
                        self.inProcess = true;

                        self.triggerStartUpload({
                            'queueLength' : this._queue.length,
                        });

                    }

                    self.triggerStartUploadFile({
                        'name' : filename,
                        'additional' :
                        {
                            'ext': ext
                        },
                    });
                },

                onComplete: function(filename, response)
                {
                    self.triggerCompleteUploadFile({
                        'name' : filename,
                        'response' : response,
                    });

                    self.queue      = this._queue.length;

                    if (this._queue.length == 0)
                    {
                        self.inProcess  = false;
                        self.triggerCompleteUpload({});
                    }
                }
            }, this.get("options")));


            if (self.uploaderObj)
            {
                self.uploaderObj.setData(self.getManager().getCommonData());
            }
        },

    });


        /**
         * Прогресс бары
         */
    /**
     * Базовый абстрактный класс
     */
    sx.classes.files._UploadProgress = sx.classes.Widget.extend({

        /**
         * @param Manager
         * @param opts
         */
        construct: function(Manager, wrapper, opts)
        {
            if (! (Manager instanceof sx.classes.files._Manager))
            {
                throw new Error('Не передан менеджер загрузки');
            }

            opts = opts || {};
            opts['manager'] = Manager;

            this.applyParentMethod(sx.classes.Widget, 'construct', [wrapper, opts]);
        },

        /**
         *
         * @returns {sx.classes.files._Manager}
         */
        getManager: function()
        {
            return this.get("manager");
        },

        /**
         * @param pct
         */
        updateProgress: function(pct)
        {
            $('.progress-bar', this.getWrapper()).css('width', Number(pct) + '%');
        }

    });

    /**
     * Глобальный прогресс бар
     */
    sx.classes.files.AllUploadProgress = sx.classes.files._UploadProgress.extend({

        _init: function()
        {
            var self = this;

            this.getManager().bind("startUpload", function(e, data)
            {
                self.updateProgress(0);
                $('.sx-uploadedFiles', self.getWrapper()).empty().append(0);
                $('.sx-allFiles', self.getWrapper()).empty().append(Number(data.queueLength));;

                self.getWrapper().show();
                //data.queueLength
            });

            this.getManager().bind("completeUpload", function(e, data)
            {
                self.getWrapper().hide();
                //data.queueLength
            });

            this.getManager().bind("onProgress", function(e, data)
            {
                self.updateProgress(data.pct);

                $('.sx-uploadedFiles', self.getWrapper()).empty().append(data.uploadedFiles);
                $('.sx-allFiles', self.getWrapper()).empty().append(data.allFiles);
            });
        },
    });

    /**
     * Прогрессбар загрузки одного файла
     */
    sx.classes.files.OneFileUploadProgress = sx.classes.files._UploadProgress.extend({
        _init: function()
        {
            var self = this;

            this.getManager().bind("startUploadFile", function(e, data)
            {
                self.updateProgress(0);
                self.getWrapper().show();

                $('.sx-uploaded-file-name', self.getWrapper()).empty().append(data.name);
            });

            this.getManager().bind("completeUploadFile", function(e, data)
            {
                self.getWrapper().hide();
            });

            this.getManager().bind("onProgressFile", function(e, data)
            {
                self.updateProgress(data.pct);
            });
        },
    });


        /**
         * Менеджеры загрузки
         */

    /**
     * Менеджер — основной базовый класс
     */
    sx.classes.files._Manager = sx.classes.Widget.extend({

        _init: function()
        {
            var self = this;

            this.sources = [];

            if (this.get('completeUpload'))
            {
                self.bind('completeUpload', function(e, data)
                {
                    var callback = self.get('completeUpload');
                    callback(data);
                });
            }

            if (this.get('completeUploadFile'))
            {
                self.bind('completeUploadFile', function(e, data)
                {
                    var callback = self.get('completeUploadFile');
                    callback(data);
                });
            }
        },

        /**
         * Общие данные передаваемые в каждый источник
         * Например в какую группу загружать файлы
         * @returns {*}
         */
        getCommonData: function()
        {
            return this.get('commonData', {});
        },

        /**
         * @param data
         * @returns {sx.classes.files._Manager}
         */
        setCommonData: function(data)
        {
            this.set('commonData', data);
            this.trigger('changeData');

            return this;
        },

        /**
         * @param data
         * @returns {sx.classes.files._Manager}
         */
        mergeCommonData: function(data)
        {
            var newData = _.extend(this.get('commonData', {}), data);
            return this.setCommonData(newData);
        },
    });

    sx.classes.files.Manager = sx.classes.files._Manager.extend({});

    /**
     * Стандартная сборка файлового менеджера
     *
     * Источники файлов:
     * SimpleUpload         //Мультизагрузка с компьютера
     * RemoteUpload         //Загрузка по http://
     * FileManagerUpload    //Выбор файлов из файлового менеджера
     */
    sx.classes.DefaultFileManager = sx.classes.files.Manager.extend({

        _init: function()
        {
            this.applyParentMethod(sx.classes.files._Manager, '_init', []); // TODO: make a workaround for magic parent calling

            this.SourceSimpleUpload = new sx.classes.files.sources.SimpleUpload(this, this.get("simpleUpload"));
            this.SourceRemoteUpload = new sx.classes.files.sources.RemoteUpload(this, this.get("remoteUpload"));
            this.SourceFileManagerUpload = new sx.classes.files.sources.FileManagerUpload(this);

            this.AllUploadProgress      = new sx.classes.files.AllUploadProgress(this,      this.get('allUploadProgressSelector', ".sx-progress-bar"));
            this.OneFileUploadProgress  = new sx.classes.files.OneFileUploadProgress(this,  this.get('oneFileUploadProgressSelector', ".sx-progress-bar-file"));

            this.bind('error', function(e, data)
            {
                sx.notify.error(data.message);
            });

            this.bind('completeUpload', function(e, data)
            {
                sx.notify.success('Загрузка завершена');
            });

            this.bind('startUpload', function(e, data)
            {
                if (data.queueLength > 2)
                {
                    sx.notify.info('Начало загрузки: ' + data.queueLength + ' (файлов)');
                }
            });
        },
    });

    /**
     *
     */
    sx.classes.CustomFileManager = sx.classes.DefaultFileManager.extend({

        _init: function()
        {
            //События на кнопки для simpleupload
            if (this.get('simpleUploadButtons', []))
            {
                var buttons = [];
                _.each(this.get('simpleUploadButtons', []), function(value, key)
                {
                    buttons.push(document.getElementById(value))
                });

                this.set('simpleUpload', _.extend(
                    this.get('simpleUpload', {}),
                    {
                        'buttons' : buttons
                    }

                ));
            }

            this.applyParentMethod(sx.classes.DefaultFileManager, '_init', []); // TODO: make a workaround for magic parent calling
        },

        _onDomReady: function()
        {
            var self = this;

            if (this.get('remoteUploadButtonSelector', ".source-remoteUpload"))
            {
                $( this.get('remoteUploadButtonSelector', ".source-remoteUpload") ).on('click', function()
                {
                    self.SourceRemoteUpload.run();
                });
            }
        },
    });


})(sx, sx.$, sx._);