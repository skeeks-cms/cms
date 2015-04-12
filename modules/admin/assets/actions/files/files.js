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
            if (! (Manager instanceof sx.classes.files._Manager))
            {
                throw new Error('Не передан менеджер загрузки');
            }

            opts = opts || {};
            opts['manager'] = Manager;

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

            this._afterInit();
        },

        _afterInit: function()
        {},

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
        triggerError: function(msg)
        {
            this.trigger("error", {
                'msg': msg
            });

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

        _init: function()
        {
            var self = this;

            $(".source-remoteUpload").click(function(){
                //По клику на кнопку, загрузить по http, рисуем textarea, предлагаем ввести пользователю ссылки на изображения, которые хотим скачать, резделив их через запятую или с новой строки.
                //По нажатию кнопки начало загрузки.
                var link = prompt("Введите URL");

                if (link)
                {
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


                        var ajax = sx.ajax.preparePostQuery(self.get('url'), {
                            'link': link
                        });

                        ajax.onComplete(function (e, data) {
                            self.triggerCompleteUploadFile({
                                'response': data
                            });

                            self.queue = self.queue - 1;

                            if (self.queue == 0) {
                                self.inProcess = false;
                                self.triggerCompleteUpload({});
                            }
                        });

                        ajax.execute();
                    });
                }
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

            this.getManager().bind("changeGroup", function(e, data)
            {
                if (self.uploaderObj)
                {
                    self.uploaderObj.setData(data);
                }
            });
        },

        _onWindowReady: function()
        {
            var self    = this;

            var button = document.getElementById(
                $(".source-simpleUpload", this.getManager().getWrapper()).attr("id")
            );

            this.uploaderObj = new ss.SimpleUpload(_.extend(this.get("options"), {
                queue: true,
                debug: false,
                maxUploads: 1,
                multiple: true,
                button: button,
                onExtError: function(filename, extension)
                {
                    self.trigger("error", "is not a permitted file type.");
                    self.trigger("error", filename + " тип файла не разрешен к загрузке");
                },
                onSizeError: function(filename, fileSize)
                {
                    self.trigger("error", filename + " слишком большой, допустимо не более " + Number(self.get("options").maxSize) + " Kb");
                },

                onProgress: function(pct)
                {
                    self.triggerOnProgressFile({
                        'pct': pct
                    });
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

                    /*if (!response)
                    {
                        self.trigger("error", "Не удалось загрузить файл");
                    }

                    if (response.success === true)
                    {
                        self.triggerCompleteUploadFile(response.file);

                    } else
                    {
                          if (response.msg)
                          {
                                self.trigger("error", response.msg);
                          } else
                          {
                                self.trigger("error", "Не удалось загрузить файл");
                          }
                    }*/
                }
            }));
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
         *
         * @param Source
         * @returns {sx.classes.files._Manager}
         */
        registerSource: function(Source)
        {
            var self = this;

            if (!Source instanceof sx.classes.files._Source)
            {
                throw new Error("Source instanceof sx.classes.files._Source");
            }

            if (!this.sources)
            {
                this.sources = [];
            }

            this.sources.push(Source);

            Source.bind("error", function(e, message)
            {
                sx.notify.error(message);
            });

            Source.bind("completeUpload", function(e, data)
            {
                self.trigger('completeUpload', data);
            });

            Source.bind("startUpload", function(e, data)
            {
                //queueLength
                self.trigger('startUpload', data);
            });

            Source.bind("startUploadFile", function(e, data)
            {
                //queueLength
                self.trigger('startUploadFile', data);
            });

            Source.bind("completeUploadFile", function(e, data)
            {
                //queueLength
                self.trigger('completeUploadFile', data);
            });

            Source.bind("onProgressFile", function(e, data)
            {
                //queueLength
                self.trigger('onProgressFile', data);
            });

            Source.bind("onProgress", function(e, data)
            {
                //queueLength
                self.trigger('onProgress', data);
            });

            return this;
        },

        _onDomReady: function()
        {
            this._initSelectForm();
        },

        /**
        *
        * @returns {sx.classes.FileManager}
        * @private
        */
        _initSelectForm: function()
        {
            var self = this;
            this.JselectType        = $('.sx-select-group', this.getWrapper());
            this.JselectTypeForm    = $('form', this.JselectType);

            this.getWrapper().on("change", ".sx-select-group select", function()
            {
                self.trigger("changeGroup", {'group': $(this).val()});

                $(this).closest("form").submit();

                _.delay(function()
                {
                    $.pjax.reload('#sx-table-files', {});
                }, 500);
                return false;
            });

            return this;
        },


        _onWindowReady: function()
        {}
    });


    /**
     * Стандартная сборка файлового менеджера
     *
     * Источники файлов:
     * SimpleUpload         //Мультизагрузка с компьютера
     * RemoteUpload         //Загрузка по http://
     * FileManagerUpload    //Выбор файлов из файлового менеджера
     */
    sx.classes.DefaultFileManager = sx.classes.files._Manager.extend({

        _init: function()
        {
            this.applyParentMethod(sx.classes.files._Manager, '_init', []); // TODO: make a workaround for magic parent calling

            this

                .registerSource(
                    new sx.classes.files.sources.SimpleUpload(this, {
                        "options" : this.get("simpleUpload"),
                    })
                )

                .registerSource(
                    new sx.classes.files.sources.RemoteUpload(this, this.get("remoteUpload")) //В этот источник передаем настройки из backend-a
                )

                .registerSource(
                    new sx.classes.files.sources.FileManagerUpload(this)
                )
            ;

            this.bind('completeUpload', function(e, data)
            {
                sx.notify.success('Файлы успешно загружены');
                if ($('#sx-table-files')[0])
                {
                    $.pjax.reload('#sx-table-files', {});
                }

            });

            this.bind('startUpload', function(e, data)
            {
                sx.notify.info('Начало загрузки: ' + data.queueLength + ' (файлов)');
            });


            this.AllUploadProgress      = new sx.classes.files.AllUploadProgress(this, ".sx-progress-bar");
            this.OneFileUploadProgress  = new sx.classes.files.OneFileUploadProgress(this, ".sx-progress-bar-file");
        }

    });


})(sx, sx.$, sx._);