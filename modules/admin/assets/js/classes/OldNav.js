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

            $(".nav-sidebar").on("click", "a", function(types) {
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

                $("li.active", $("ul.nav-sidebar")).parent().parent().children('a').click();


              //dropSidebarShadow();
              //$(".sidebar").mmenu();

        }

    });
})(sx, sx.$, sx._);