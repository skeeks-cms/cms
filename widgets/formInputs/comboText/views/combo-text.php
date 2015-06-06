<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget */

$options = $widget->clientOptions;
$clientOptions = \yii\helpers\Json::encode($options);
?>
<div id="<?= $widget->id; ?>">
    <div class="sx-select-controll">
        <?= \yii\helpers\Html::radioList($widget->id . '-radio', 'html-source', [
            'text'          => 'Текст',
            'html-editor' => 'Визуальный редактор',
            'html-source'   => 'Исходный код',
        ])?>
    </div>
    <div class="sx-controll">
        <?= $textarea; ?>
    </div>
</div>

<?
//TODO: убрать в файл

$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes.combotext', sx);
        sx.createNamespace('classes.combotext.controlls', sx);


        sx.classes.combotext.controlls._Base = sx.classes.Component.extend({

            construct: function (ComboTextInputWidget, opts)
            {
                var self = this;
                opts = opts || {};
                if (!ComboTextInputWidget instanceof sx.classes.combotext.ComboTextInputWidget)
                {
                    throw new Error('Некорректный виджет для этого контрола');
                }

                this.widget = ComboTextInputWidget;
                //this.parent.construct(opts);
                this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
            },

            _init: function()
            {},

            run: function()
            {

            },

            stop: function()
            {

            },
        });

        sx.classes.combotext.controlls.Ckeditor = sx.classes.combotext.controlls._Base.extend({

            _init: function()
            {
                this.ckeditorInited = false;
            },

            run: function()
            {
                var self = this;

                this.onWindowReady(function()
                {
                    self._run();
                });
            },

            _run: function()
            {
                CKEDITOR.replace(this.widget.get('inputId'), this.widget.get('ckeditor'));

                this.getInstance().updateElement();
                //При первом запуске будет запущен этот код.
                if (this.ckeditorInited === false)
                {
                    skeeks.ckEditorWidget.registerOnChangeHandler(this.get('inputId'));
                    if (this.widget.get('ckeditor').filebrowserUploadUrl)
                    {
                        skeeks.ckEditorWidget.registerCsrfImageUploadHandler();
                    }

                    this.ckeditorInited = true;
                }
            },

            /**
            *
            * @returns {sx.classes.combotext.controlls.ControllCkeditor}
            */
            stop: function()
            {
                if (this.getInstance())
                {
                    this.getInstance().destroy();
                }

                return this;
            },

            /**
            *
            * @returns {CKEDITOR.editor}
            */
            getInstance: function()
            {
                return CKEDITOR.instances[this.widget.get('inputId')];
            }
        });



        sx.classes.combotext.controlls.Codemirror = sx.classes.combotext.controlls._Base.extend({

            _init: function()
            {
                this.Instance = null;
            },

            run: function()
            {
                var self = this;
                this.Instance = CodeMirror.fromTextArea(document.getElementById(this.widget.get('inputId')));
                var options = this.widget.get('codemirror');
                console.log(self.Instance);
                if (options)
                {
                    _.each(options, function(val, key)
                    {
                        self.Instance.setOption(key, val);
                    });
                }

                self.Instance.focus();

                return this;
            },


            /**
            *
            * @returns {sx.classes.combotext.controlls.ControllCkeditor}
            */
            stop: function()
            {
                if (this.Instance)
                {
                    this.Instance.save();
                    this.Instance.display.wrapper.remove();

                    $("#" + this.widget.get('inputId')).show();
                }

                return this;
            },


        });


        //Основной объект
        sx.classes.combotext.ComboTextInputWidget = sx.classes.Component.extend({

            _init: function()
            {
                this.ControllCkeditor   = new sx.classes.combotext.controlls.Ckeditor(this);
                this.ControllCodemirror   = new sx.classes.combotext.controlls.Codemirror(this);
                this.currentControll    = null;
            },

            _onDomReady: function()
            {
                var self = this;
                this.jQueryWrapper = $('#' + this.get('id'));

                $(".sx-select-controll input[type=radio]", this.jQueryWrapper).on('change', function()
                {
                    self.goControll($(this).val());
                    return false;
                });

                self.goControll($(".sx-select-controll input[type=radio]:checked", this.jQueryWrapper).val());
            },

            goControll: function(code)
            {
                if (this.currentControll)
                {
                    this.currentControll.stop();
                }

                if (code == 'html-editor')
                {
                    this.currentControll = this.ControllCkeditor;
                    this.ControllCkeditor.run();

                } else if (code == 'text')
                {

                } else if (code == 'html-source')
                {
                    this.currentControll = this.ControllCodemirror;
                    this.ControllCodemirror.run();
                } else
                {
                    sx.notify.error('Еще не реализовано');
                }
            },

        });

        new sx.classes.combotext.ComboTextInputWidget({$clientOptions});

    })(sx, sx.$, sx._);
JS
)
?>
