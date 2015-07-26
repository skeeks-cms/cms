/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.07.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.grid', sx);

    sx.classes.grid.Standart = sx.classes.Component.extend({

        _init: function()
        {
            this.CheckFullAll   = new sx.classes.grid.CheckFullAll(this);
            this.CheckAll       = new sx.classes.grid.CheckAll(this);
        },

        _onDomReady: function()
        {
            var self = this;

            this.JQueryGrid                 = $("#" + this.get('id'));
            this.JQueryMultiBtnsWrapper     = $(".sx-grid-multi-controlls");

            this.JQueryMultiBtns = $('.sx-grid-multi-btn', this.JQueryMultiBtnsWrapper);

            this.JQueryMultiBtns.on('click', function()
            {
                var id          = $(this).data('id');
                var actionData  = _.find(self.get("actions"), function(raw)
                {
                    return raw.id == id;
                });

                if (actionData)
                {
                    new sx.classes.grid.MultiAction(self, actionData);
                }
            });

            this.CheckFullAll.bind("change", function()
            {
                self.updateMultiBtns();
                return this;
            });

            this.CheckAll.bind("change", function()
            {
                self.updateMultiBtns();
                return this;
            });

            self.updateMultiBtns();
        },

        updateMultiBtns: function()
        {
            if (!this.CheckFullAll.isChecked() && !this.CheckAll.isChecked())
            {
                this.JQueryMultiBtns.attr("disabled", "disabled");
            } else
            {
                this.JQueryMultiBtns.attr("disabled", false);
            }

            return this;
        },

        /**
         * @returns {void|*|Function|*}
         */
        getBlocker: function()
        {
            if (!this.Blocker)
            {
                var pjaxId = this.get('pjaxId');
                if (pjaxId)
                {
                    this.Blocker = new sx.classes.Blocker("#" + pjaxId);
                } else
                {
                    this.Blocker = new sx.classes.Blocker("#" + this.get('id'));
                }
            }

            return this.Blocker;
        },

        reload: function()
        {
            if (this.get('enabledPjax'))
            {
                var pjaxId = this.get('pjaxId');
                $.pjax.reload('#' + pjaxId, {});
                return this;
            } else
            {
                window.location.reload();
            }

            return this;
        }
    });

    /**
     * Абстракный элемент грида
     */
    sx.classes.grid._Element = sx.classes.Component.extend({
        construct: function (Grid, opts)
        {
            var self = this;
            opts = opts || {};
            this.Grid = Grid;
            //this.parent.construct(opts);
            this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
        },
    });

    sx.classes.grid.MultiAction = sx.classes.grid._Element.extend({

        _init: function()
        {
            var self = this;

            if (!this.Grid.CheckFullAll.isChecked() && !this.Grid.CheckAll.isChecked())
            {
                sx.notify.info("Необходимо выбрать хотя бы один элемент.");
                return this;
            }

            if (this.get("confirm"))
            {
                sx.confirm(this.get("confirm"), {
                    'no' : function()
                    {},
                    'yes' : function()
                    {
                        self._go();
                    }
                });
            } else
            {
                return this._go();
            }
        },

        _go: function()
        {
            var self = this;
            //Надо делать ajax запрос
            if (this.get("request") == 'ajax')
            {
                if (this.get("method", "post") == "post")
                {
                    this.ajax = sx.ajax.preparePostQuery(this.get('url'));
                } else
                {
                    this.ajax = sx.ajax.prepareGetQuery(this.get('url'));
                }

                var data = {"all": self.Grid.CheckFullAll.getValue()};
                data[self.Grid.get('requestPkParamName', 'pk')] = self.Grid.CheckAll.getValue();
                this.ajax.setData(data);

                self.Grid.getBlocker().block();

                new sx.classes.AjaxHandlerNoLoader(this.ajax);

                this.ajax.onSuccess(function(e, data)
                {
                    if (data.response.success)
                    {
                        sx.notify.success(data.response.message);
                        self.Grid.reload();
                    } else
                    {
                        sx.notify.error(data.response.message);
                        self.Grid.reload();
                    }

                    self.Grid.getBlocker().unblock();
                });

                this.ajax.onError(function(e, response)
                {
                    sx.notify.error(response.errorThrown + '. Обратитесь к разарботчикам');
                    self.Grid.getBlocker().unblock();
                    self.Grid.reload();
                });

                this.ajax.execute();
                return this;
            }
        },
    });

    sx.classes.grid.CheckAll = sx.classes.grid._Element.extend({

        _onDomReady: function()
        {
            var self = this;

            this.JQueryCheckboxAll = $(".select-on-check-all", this.Grid.JQueryGrid);
            this.JQueryCheckbox = $(".sx-admin-grid-checkbox", this.Grid.JQueryGrid);
            this.JQueryCheckbox.on("change", function()
            {
                self.trigger("change");
            });

            this.JQueryCheckboxAll.on("change", function()
            {
                _.delay(function()
                {
                    self.trigger("change");
                }, 200);
            });
        },

        /**
         * @returns {Array}
         */
        getValue: function()
        {
            var result = [];
            this.JQueryCheckbox.each(function(e, data)
            {
                if ($(this).is(":checked"))
                {
                    result.push( Number($(this).val()) );
                }
            });

            return result;
        },

        /**
         * @returns {Boolean|boolean}
         */
        isChecked: function()
        {
            return Boolean(_.size(this.getValue()) > 0 );
        }

    });

    sx.classes.grid.CheckFullAll = sx.classes.grid._Element.extend({

        _onDomReady: function()
        {
            var self = this;

            this.JQueryCheckbox = $(".sx-select-full-all", this.Grid.JQueryGrid);
            this.JQueryCheckbox.on('change', function()
            {
                var ThisJquery = $(this);
                if (ThisJquery.is(':checked'))
                {
                    sx.confirm('Вы уверены что хотите применить действие для ВСЕХ записей в списке, в том числе на других страницах списка, а не только для отмеченных флажками?', {
                        'no': function(e, data)
                        {
                            ThisJquery.attr("checked", false);
                            self.trigger("change");
                        },
                        'yes': function(e, data)
                        {}
                    });
                }

                self.trigger("change");
            });
        },

        /**
         * @returns {Boolean|boolean}
         */
        isChecked: function()
        {
            return Boolean( this.JQueryCheckbox.is(":checked") );
        },

        /**
         * @returns {number}
         */
        getValue: function()
        {
            if (this.isChecked())
            {
                return 1;
            }

            return 0;
        }
    });





})(sx, sx.$, sx._);
