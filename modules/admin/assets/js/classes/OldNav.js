/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.02.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes', sx);
    /**
     * TODO: разгрести эту кашу. Половина всего этого не используется оставляю на всякий случай.
     * Устаревшая хрень, от которой нужно избавиться и привести в порядок
     * @type {void|*|Function}
     */
    sx.classes.MainNav = sx.classes.Component.extend({

        _onDomReady: function()
        {
            this.initHeadMenu();
            this.initMenu();
        },

        initHeadMenu: function()
        {
            var self = this;

            $('.sidebar-menu .sx-head').on('click', function()
            {
                var Block = $(this).parent();
                if (Block.hasClass('sx-opened'))
                {
                    self._closeSxHead(Block);
                } else
                {
                    self._openSxHead(Block);
                }
            });
        },

        _openSxHead: function(jQuery)
        {
            //Закрыть все остальные
            var self = this;
            $('.sidebar-menu.sx-opened').each(function()
            {
                self._closeSxHead($(this));
            });

            jQuery.children('ul').slideDown("fast", function()
            {
                jQuery.addClass('sx-opened');
            });
        },

        _closeSxHead: function(jQuery)
        {
            jQuery.children('ul').slideUp("fast", function()
            {
                jQuery.removeClass('sx-opened');
            });
        },

        initMenu: function()
        {
            $(".nav-sidebar>li>a").on("click", function(types) {
                if ($.ajaxLoad) {
                  types.preventDefault();
                }
                if (!$(this).parent().hasClass("hover"))
                {
                  if ($(this).parent().find("ul").size() != 0)
                  {
                    if ($(this).parent().hasClass("opened"))
                    {
                      $(this).parent().removeClass("opened");
                    } else
                    {
                      $(this).parent().addClass("opened");
                    }
                    $(this).parent().find("ul").first().slideToggle("fast", function() {
                      //dropSidebarShadow();
                    });
                    $(this).parent().parent().find("ul").each(function()
                    {
                      if (!$(this).parent().hasClass("opened"))
                      {
                        $(this).slideUp();
                      }
                    });
                    if (!$(this).parent().parent().parent().hasClass("opened")) {
                      $(".nav a").not(this).parent().find("ul").slideUp("fast", function() {
                        $(this).parent().removeClass("opened").find(".opened").each(function() {
                          $(this).removeClass("opened");
                        });
                      });
                    }
                  } else
                  {
                    if (!$(this).parent().parent().parent().hasClass("opened")) {
                      $(".nav a").not(this).parent().find("ul").slideUp("fast", function() {
                        $(this).parent().removeClass("opened").find(".opened").each(function() {
                          $(this).removeClass("opened");
                        });
                      });
                    }
                  }
                }
              });

            var jQueryActiveLi = $("li.active", $("ul.nav-sidebar"));
            var jQueryLi = jQueryActiveLi.parent().parent('li');
            jQueryLi.addClass('opened');
            jQueryLi.children("ul").slideDown();
            jQueryActiveLi.children("ul").slideDown();
        }

    });
})(sx, sx.$, sx._);