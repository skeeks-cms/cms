/*!
 *
 *
 *
 * @date 17.10.2014
 * @copyright skeeks.com
 * @author Semenov Alexander <semenov@skeeks.com>
 */

(function(sx, $, _)
{
    sx.createNamespace('classes.widgets', sx);
    sx.createNamespace('classes.sources', sx);

    sx.classes.widgets.StorageFileManager = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            var slef = this;
            this.$_wrapper = $("#" + this.get("id"));

            this.$_sourceSimpleUpload   = $(".source-simpleUpload", this.$_wrapper);
            this.$_filesContainer       = $(".sx-files", this.$_wrapper);
            this.$_fileDeleteBtn        = $(".sx-file-controll-delete", this.$_wrapper);

            var simpleOptions = _.extend(this.get("SimpleUpload"),
                {
                    button: document.getElementById(this.$_sourceSimpleUpload.attr("id"))
                }
            );

            this._sourceSimpleUpload = new sx.classes.sources.SimpleUpload({
                "options" : simpleOptions,
                "manager" : this
            });

            this._sourceSimpleUpload.bind("error", function(e, message)
            {
                alert(message);
            });

            this._sourceSimpleUpload.bind("fileUploaded", function(e, file)
            {
               // console.log(file);
               // self.$_filesContainer.append("<li><img src='" + file.src + "' width='100'/></li>");
                window.location.reload();
            });


            this.$_fileDeleteBtn.on("click", function()
            {
                var $elementLi = $(this).closest("li");
                var ajax = sx.ajax.preparePostQuery($(this).attr("href"));
                ajax.onSuccess(function(e, data)
                {
                    $elementLi.remove();
                    window.location.reload();
                });
                ajax.execute();
                return false;
            });

        },

        $getProgressContainer: function()
        {
            return $(".sx-progress-bar", this.$_wrapper);
        },

        _onWindowReady: function()
        {}
    });


    sx.classes.sources.Base = sx.classes.Component.extend({

        _init: function()
        {},

        triggerFileUploaded: function(dataFileUploaded)
        {
            this.trigger("fileUploaded", dataFileUploaded);
        }

    });

    /**
     * Источник Simpleajaxuploader
     * @type {*|Function|void}
     */
    sx.classes.sources.SimpleUpload = sx.classes.sources.Base.extend({

        _init: function()
        {},

        /**
         *
         * @returns {sx.classes.widgets.StorageFileManager}
         */
        getManager: function()
        {
            return this.get("manager");
        },

        _onWindowReady: function()
        {
            var self    = this;
            var btn     = document.getElementById('upload-btn'),
                wrap        = document.getElementById('pic-progress-wrap');

            var uploader = new ss.SimpleUpload(_.extend(this.get("options"), {
                queue: false,
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

                        self.getManager().$getProgressContainer().append(prog);

                        this.setProgressBar(bar);
                        this.setProgressContainer(prog);
                        this.setFileSizeBox(size);

                  },
                startXHR: function() {

                   var abort = document.createElement('button');

                    //wrap.appendChild(abort);
                    self.getManager().$getProgressContainer().append(abort);

                    abort.className = 'btn btn-sm btn-info';
                    abort.innerHTML = 'Cancel';

                    this.setAbortBtn(abort, true);
                },
                onComplete: function(filename, response)
                {
                    if (!response)
                    {
                        self.trigger("error", "Не удалось загрузить файл");
                    }

                    if (response.success === true)
                    {
                        self.triggerFileUploaded(response.file);

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

})(sx, sx.$, sx._);