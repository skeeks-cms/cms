/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.06.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.ssh', sx);

    sx.classes.SshConsole = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            new sx.classes.ssh.Autocomplete({
                'SshConsole': this
            });

            new sx.classes.ssh.History({
                'SshConsole': this
            });


            /**
             * Windows variables.
             */
            window.currentDir = this.get('currentDirName');
            window.currentDirName = window.currentDir.split('/').pop();
            window.currentUser = this.get('currentUser');;
            window.titlePattern = "* — console";
            window.document.title = window.titlePattern.replace('*', window.currentDirName);


            this.bind('success', function(e, data)
            {
                _.delay(function(){
                    self.input.focus()
                }, 200);
            });

            this.bind('error', function(e, data)
            {
                _.delay(function(){
                    self.input.focus()
                }, 200);
            });
        },


        scroll: function()
        {
            window.scrollTo(0, document.body.scrollHeight);
        },


        _onDomReady: function()
        {
            var self = this;

            var screen = $('pre');
            var input = $('input').focus();
            var form = $('form');

            input.history();
            input.autocomplete(this.get('autocomplete'));

            this.input  = input;
            this.form   = form;

            $('html, body').on('click', function()
            {
                self.input.focus();
            });
            self.input.focus();

            form.submit(function ()
            {
                var command = $.trim(input.val());
                if (command == '') {
                    return false;
                }
                $("<code>" + window.currentDirName + "&nbsp;" + window.currentUser + "$&nbsp;" + command + "</code><br>").appendTo(screen);
                self.scroll();
                input.val('');
                form.hide();
                input.addHistory(command);

                self.trigger('submit', {
                    'SshConsole': self,
                    'command': command,
                    'cd': window.currentDir,
                });


                $.get('', {'command': command, 'cd': window.currentDir}, function (output) {
                    var pattern = /^set current directory (.+?)$/i;
                    if (matches = output.match(pattern)) {
                        window.currentDir = matches[1];
                        window.currentDirName = window.currentDir.split('/').pop();
                        $('#currentDirName').text(window.currentDirName);
                        window.document.title = window.titlePattern.replace('*', window.currentDirName);
                    } else {
                        screen.append(output);
                    }
                })
                    .fail(function () {

                        self.trigger('error', {
                            'SshConsole': self,
                            'command': command,
                            'cd': window.currentDir,
                        });

                        screen.append("<span class='error'>Command is sent, but due to an HTTP error result is not known.</span>\n");
                    })
                    .always(function () {

                        form.show();
                        self.scroll();

                        self.trigger('success', {
                            'SshConsole': self,
                            'command': command,
                            'cd': window.currentDir,
                        });
                    });
                return false;
            });
            $(document).keydown(function () {
                input.focus();
            });
        }
    });

    sx.classes.ssh.History = sx.classes.Component.extend({
        _init: function()
        {
            this.SshConsole = this.get('SshConsole');
        },

        _onDomReady: function()
        {
            var self = this;

            /**
             *  History of commands.
             */
            (function ($) {
                var maxHistory = 100;
                var position = -1;
                var currentCommand = '';
                var addCommand = function (command) {
                    var ls = localStorage['commands'];
                    var commands = ls ? JSON.parse(ls) : [];
                    if (commands.length > maxHistory) {
                        commands.shift();
                    }
                    commands.push(command);
                    localStorage['commands'] = JSON.stringify(commands);
                };
                var getCommand = function (at) {
                    var ls = localStorage['commands'];
                    var commands = ls ? JSON.parse(ls) : [];
                    if (at < 0) {
                        position = at = -1;
                        return currentCommand;
                    }
                    if (at >= commands.length) {
                        position = at = commands.length - 1;
                    }
                    return commands[commands.length - at - 1];
                };
                $.fn.history = function () {
                    var input = $(this);
                    input.keydown(function (e) {
                        var code = (e.keyCode ? e.keyCode : e.which);
                        if (code == 38) { // Up
                            if (position == -1) {
                                currentCommand = input.val();
                            }
                            input.val(getCommand(++position));
                            self.SshConsole.scroll();
                            return false;
                        } else if (code == 40) { // Down
                            input.val(getCommand(--position));
                            self.SshConsole.scroll();
                            return false;
                        } else {
                            position = -1;
                        }
                    });
                    return input;
                };
                $.fn.addHistory = function (command) {
                    addCommand(command);
                };
            })(jQuery);
        }
    });

    sx.classes.ssh.Autocomplete = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            (function ($) {
                $.fn.autocomplete = function (suggest) {
                    // Wrap and extra html to input.
                    var input = $(this);
                    input.wrap('<span class="autocomplete" style="position: relative;"></span>');
                    var html =
                        '<span class="overflow" style="position: absolute; z-index: -10;">' +
                            '<span class="repeat" style="opacity: 0;"></span>' +
                            '<span class="guess"></span></span>';
                    $('.autocomplete').prepend(html);
                    // Search of input changes.
                    var repeat = $('.repeat');
                    var guess = $('.guess');
                    var search = function (command) {
                        var array = [];
                        for (var key in suggest) {
                            if (!suggest.hasOwnProperty(key))
                                continue;
                            var pattern = new RegExp(key);
                            if (command.match(pattern)) {
                                array = suggest[key];
                            }
                        }
                        var text = command.split(' ').pop();
                        var found = '';
                        if (text != '') {
                            for (var i = 0; i < array.length; i++) {
                                var value = array[i];
                                if (value.length > text.length &&
                                    value.substring(0, text.length) == text) {
                                    found = value.substring(text.length, value.length);
                                    break;
                                }
                            }
                        }
                        guess.text(found);
                    };
                    var update = function () {
                        var command = input.val();
                        repeat.text(command);
                        search(command);
                    };
                    input.change(update);
                    input.keyup(update);
                    input.keypress(update);
                    input.keydown(update);
                    input.keydown(function (e) {
                        var code = (e.keyCode ? e.keyCode : e.which);
                        if (code == 9) {
                            var val = input.val();
                            input.val(val + guess.text());
                            return false;
                        }
                    });
                    return input;
                };
            })(jQuery);
        }
    });

})(sx, sx.$, sx._);