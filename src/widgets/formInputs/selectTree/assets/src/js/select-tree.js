/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.treeinput', sx);

    sx.classes.treeinput.SelectedUl = sx.classes.Component.extend({

        construct: function (TreeInput, opts)
        {
            var self = this;
            opts = opts || {};
            this._TreeInput = TreeInput;
            this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
        },

        remove: function(id)
        {
            $("[data-id=" + id + "]", this._TreeInput.getJSelectedContainer()).remove();
        },

        /**
         * @param id
         * @param name
         * @returns {sx.classes.treeinput.SelectedUl}
         */
        add: function(id, name, url)
        {
            var jWrapper = this._TreeInput.getJSelectedContainer();

            var jLi = $('<li>', {'data-id': id})
                .append($("<a>", {'target': '_blank', 'href': url}).text(name))
                .append($("<a>", {'href': '#', 'class': 'sx-close-btn pull-right'}).append('<i class="fa fa-times"></i>'))
            ;

            jWrapper.append(jLi);

            //this.blink(jLi, 1);

            return this;
        },

        blink: function(jElement, count)
        {
            var self = this;

            jElement.animate({ backgroundColor: "#fee3ea" }, {
                duration: 100,
                complete: function() {

                    // reset
                    jElement.delay(100).animate({ backgroundColor: "none" }, {
                        duration: 100,
                        complete: function() {

                            // maybe call next round
                            if(count > 1) {
                                self.blink(jElement, --count);
                            }
                        }
                    });

                }
            });
        },

        _onDomReady: function()
        {
            var self = this;
            this._TreeInput.getJSelectedContainer().on('click', ".sx-close-btn", function()
            {
                var id = $(this).closest('li').data('id');
                self._TreeInput.unSelectValue(id);
                return false;
            });
        },
    });

    sx.classes.treeinput._Core = sx.classes.Component.extend({

        _init: function()
        {
            this.SelectedUl = new sx.classes.treeinput.SelectedUl(this);
        },

        _onDomReady: function()
        {
        },

        getJWrapper: function()
        {
            return $('#' + this.get('wrapperid'))
        },

        getJElement: function()
        {
            return $('.sx-widget-element', this.getJWrapper())
        },

        getJSelectedContainer: function()
        {
            return $('.sx-selected', this.getJWrapper())
        },

        getJPjax: function()
        {
            return $('#' + this.get('pjaxid'))
        },

        /**
         * @returns {*}
         */
        getValue: function()
        {
            return this.getJElement().val();
        },

        selectValue: function(value, jQueryElement)
        {
            var self = this;
            self.getJElement().append(
                $("<option>", {'value': value, 'selected': 'selected'}).text(value)
            );

            var jNode = jQueryElement.closest('.sx-tree-node');
            var name = self.getNodeName(jNode);
            this.SelectedUl.add(value, name, jNode.data('url'));

            self.getJElement().change();

            return this;
        },

        unSelectValue: function(value)
        {
            var self = this;
            $("option[value='" + value + "']", self.getJElement()).remove();
            self.SelectedUl.remove(value);

            self.getJElement().change();
            return this;
        },

        /**
         * @param jNode
         * @returns {*}
         */
        getFullPath: function(jNode)
        {
            var path = this._getFullPath(jNode);
            var path2 = path.reverse()
            return path2;
        },

        /**
         * @param jNode
         * @returns {*|string}
         */
        getNodeName: function(jNode)
        {
            var path = this.getFullPath(jNode);
            return path.join(" / ");
        },

        /**
         * @param jNode
         * @param result
         * @returns {*|Array}
         * @private
         */
        _getFullPath: function(jNode, result)
        {
            result = result || [];

            var name = jNode.children().children('.sx-label-node').find('a').text();
            result.push(name);

            var jParentNode = jNode.parent().parent('.sx-tree-node');
            if (jParentNode.length)
            {
                this._getFullPath(jParentNode, result);
            }

            return result;
        }

    });

    sx.classes.treeinput.SelectTreeInputMultiple = sx.classes.treeinput._Core.extend({

        _onDomReady: function()
        {
            var self = this;
            var jWrapper = this.getJWrapper();

            this.getJCheckbox().on('change', function()
            {
                if ($(this).is(":checked"))
                {
                    self.selectValue($(this).val(), $(this));
                } else
                {
                    self.unSelectValue($(this).val());
                }

                self.trigger("change", {
                    'value': self.get('value'),
                });
            });

            if (this.getValue())
            {
                _.each(this.getValue(), function(id)
                {
                    var Jelement = $(".sx-checkbox[value='" + id + "']", jWrapper);
                    if (!Jelement.is(":checked"))
                    {
                        Jelement.attr("checked", "checked");
                    };
                });
            }

        },

        unSelectValue: function(value)
        {
            var self = this;
            $("option[value='" + value + "']", self.getJElement()).remove();
            self.SelectedUl.remove(value);


            var jWrapper = this.getJWrapper();
            var Jelement = $(".sx-checkbox[value='" + value + "']", jWrapper);
            if (Jelement.is(":checked"))
            {
                Jelement.removeAttr("checked");
            };

            self.getJElement().change();

            return this;
        },

        getJCheckbox: function()
        {
            return $('.sx-checkbox', this.getJWrapper());
        },

        getJCheckboxChecked: function()
        {
            return $('.sx-checkbox:checked', this.getJWrapper());
        },
    });

    sx.classes.treeinput.SelectTreeInputSingle = sx.classes.treeinput._Core.extend({
        _onDomReady: function()
        {
            var self = this;
            var jWrapper = this.getJWrapper();

            this.getJRadio().on('change', function()
            {
                self.unSelectValue(self.getJElement().val(), false);

                if ($(this).is(":checked"))
                {
                    self.selectValue($(this).val(), $(this));
                } else
                {
                    self.unSelectValue($(this).val(), true);
                }

                self.trigger("change", {
                    'value': self.get('value'),
                });
            });

            if (this.getValue())
            {
                var Jelement = $(".sx-radio[value='" + this.getValue() + "']", jWrapper);
                if (!Jelement.is(":checked"))
                {
                    Jelement.attr("checked", "checked");
                };
            }
        },

        unSelectValue: function(value, isTriggerChange)
        {
            var self = this;
            $("option[value='" + value + "']", self.getJElement()).remove();
            self.SelectedUl.remove(value);

            var jWrapper = this.getJWrapper();
            var Jelement = $(".sx-radio[value='" + value + "']", jWrapper);
            if (Jelement.is(":checked"))
            {
                Jelement.removeAttr("checked");
            };

            if (isTriggerChange !== false)
            {
                self.getJElement().change();
            }

            return this;
        },

        getJRadio: function()
        {
            return $('.sx-radio', this.getJWrapper());
        },

        getJRadioChecked: function()
        {
            return $('.sx-radio:checked', this.getJWrapper());
        },
    });



})(sx, sx.$, sx._);