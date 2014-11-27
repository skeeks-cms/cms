/*!
 *
 * Файловый менеджер
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.11.2014
 * @since 1.0.0
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.files', sx);
    sx.createNamespace('classes.files.sources', sx);



    /**
    * Источник получения файлов
    * @type {*|void|Function}
    * @private
    */
    sx.classes.files.sources.Base = sx.classes.Component.extend({

        _init: function()
        {
            /*this.trigger('filesAdded');
            this.trigger('error', {
                'msg': 'Ошибка'
            });*/
        },

        /**
         * @param data
         * @returns {sx.classes.files.sources.Base}
         */
        triggerFileAdded: function(data)
        {
            this.trigger("fileAdded", data);
            return this;
        },

        /**
         * @param data
         * @returns {sx.classes.files.sources.Base}
         */
        triggerFilesAdded: function(data)
        {
            this.trigger("filesAdded", data);

            return this;
        },

        /**
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
         * @returns {sx.classes.files.Manager}
         */
        getManager: function()
        {
            return this.get("manager");
        },
    });


    /**
     * Источник Simpleajaxuploader
     * @type {*|Function|void}
     */
    sx.classes.files.sources.SimpleUpload = sx.classes.files.sources.Base.extend({

        _init: function()
        {
            var self = this;
            //Загрузка идет
            this._tmpUploadProcess = false;

            //После добавления файла, через 100 мс пробуем
            this.bind('fileAdded', function(e, data)
            {
                _.delay(function()
                {
                    self.triggerEndUploads();
                }, 50);
            });
        },

        /**
         * Сообщить что загрузка завершена
         * @returns {sx.classes.files.sources.SimpleUpload}
         */
        triggerEndUploads: function()
        {
            if (this._tmpUploadProcess === true)
            {
                return this;
            }

            this.triggerFilesAdded({});
            return this;
        },

        _onWindowReady: function()
        {
            var self    = this;
            var btn     = document.getElementById('upload-btn'),
                wrap        = document.getElementById('pic-progress-wrap');

            var uploader = new ss.SimpleUpload(_.extend(this.get("options"), {
                queue: true,
                maxUploads: 1,
                multiple: true,
                onExtError: function(filename, extension)
                {
                    self.trigger("error", "is not a permitted file type.");
                    self.trigger("error", filename + " тип файла не разрешен к загрузке");
                },
                onSizeError: function(filename, fileSize)
                {
                    self.trigger("error", filename + " слишком большой, допустимо не более " + Number(self.get("options").maxSize) + " Kb");
                },
                endXHR: function(filename, uploadBtn)
                {
                    //console.log(uploader.getQueueSize());
                },
                onChange: function(filename, extension, uploadBtn)
                {
                },
                onSubmit: function(filename, ext)
                {
                    self._tmpUploadProcess = true;

                   var prog = document.createElement('div'),
                       outer = document.createElement('div'),
                       bar = document.createElement('div'),
                       size = document.createElement('div');

                        prog.className = 'prog';
                        size.className = 'size';
                        outer.className = 'progress progress-striped active';
                        bar.className = 'progress-bar progress-bar-success';

                        outer.appendChild(bar);
                        prog.innerHTML = '<span style="vertical-align:middle;">'+self.safeTags(filename)+' - </span>';
                        prog.appendChild(size);
                        prog.appendChild(outer);

                        self.getManager().getJProgressContainer().append(prog);

                        this.setProgressBar(bar);
                        this.setProgressContainer(prog);
                        this.setFileSizeBox(size);
                },
                startXHR: function() {

                   var abort = document.createElement('button');

                    //wrap.appendChild(abort);
                    self.getManager().getJProgressContainer().append(abort);

                    abort.className = 'btn btn-sm btn-info';
                    abort.innerHTML = 'Cancel';

                    this.setAbortBtn(abort, true);
                },

                onComplete: function(filename, response)
                {
                    self._tmpUploadProcess = false;

                    if (!response)
                    {
                        self.trigger("error", "Не удалось загрузить файл");
                    }

                    if (response.success === true)
                    {
                        self.triggerFileAdded(response.file);

                    } else
                    {
                          if (response.msg)
                          {
                                self.trigger("error", response.msg);
                          } else
                          {
                                self.trigger("error", "Не удалось загрузить файл");
                          }
                    }
                  }
            }));
        },

        /**
         * @param str
         * @returns {string}
         */
        safeTags: function(str)
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
    *
    * @type {*|void|Function}
    */
    sx.classes.files.Manager = sx.classes.Widget.extend({

        _init: function()
        {
            console.log(this.getOpts());
            this._JsourceSimpleUpload   = $(".source-simpleUpload", this.getWrapper());
            var simpleOptions = _.extend(this.get("simpleUpload"),
                {
                    button: document.getElementById(this._JsourceSimpleUpload.attr("id"))
                }
            );

            this._sourceSimpleUpload = new sx.classes.files.sources.SimpleUpload({
                "options" : simpleOptions,
                "manager" : this
            });

            this._sourceSimpleUpload.bind("error", function(e, message)
            {
                sx.classes.modal.Alert(message);
            });

            this._sourceSimpleUpload.bind("filesAdded", function(e, file)
            {
                window.location.reload();
            });
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

            $('select', this.JselectTypeForm).on('change', function()
            {
                self.JselectTypeForm.submit();
                return false;
            });

            return this;
        },

        /**
        *
        * @returns {*|jQuery|HTMLElement}
        */
        getJProgressContainer: function()
        {
            return $(".sx-progress-bar", this.getWrapper());
        },

        _onWindowReady: function()
        {}
    });



})(sx, sx.$, sx._);