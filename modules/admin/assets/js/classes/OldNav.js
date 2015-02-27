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

        _init: function()
        {},

        _onDomReady: function()
        {
            if (window.opener)
            {
                $("body").addClass("empty");
            }

            $("ul.nav-sidebar").find("a").each(function() {
                /** @type {string} */
                var line = String(window.location);
                if (line.substr(line.length - 1) == "#") {
                  /** @type {string} */
                  line = line.slice(0, -1);
                }
                if ($($(this))[0].href == line) {
                  $(this).parent().addClass("active");
                  $(this).parents("ul").add(this).each(function() {
                    $(this).show().parent().addClass("opened");
                  });
                }
              });
              $(".nav-sidebar").on("click", "a", function(types) {
                if ($.ajaxLoad) {
                  types.preventDefault();
                }
                if (!$(this).parent().hasClass("hover"))
                {
                  if ($(this).parent().find("ul").size() != 0) {
                    if ($(this).parent().hasClass("opened")) {
                      $(this).parent().removeClass("opened");
                    } else {
                      $(this).parent().addClass("opened");
                    }
                    $(this).parent().find("ul").first().slideToggle("slow", function() {
                      //dropSidebarShadow();
                    });
                    $(this).parent().parent().find("ul").each(function() {
                      if (!$(this).parent().hasClass("opened")) {
                        $(this).slideUp();
                      }
                    });
                    if (!$(this).parent().parent().parent().hasClass("opened")) {
                      $(".nav a").not(this).parent().find("ul").slideUp("slow", function() {
                        $(this).parent().removeClass("opened").find(".opened").each(function() {
                          $(this).removeClass("opened");
                        });
                      });
                    }
                  } else {
                    if (!$(this).parent().parent().parent().hasClass("opened")) {
                      $(".nav a").not(this).parent().find("ul").slideUp("slow", function() {
                        $(this).parent().removeClass("opened").find(".opened").each(function() {
                          $(this).removeClass("opened");
                        });
                      });
                    }
                  }
                }
              });
              $(".nav-sidebar > li").hover(function() {
                if ($("body").hasClass("sidebar-minified")) {
                  $(this).addClass("opened hover");
                }
              }, function() {
                if ($("body").hasClass("sidebar-minified")) {
                  $(this).removeClass("opened hover");
                }
              });



              $("#sidebar-menu").click(function() {
                $(".sidebar").trigger("open");
              });
              $("#sidebar-minify").click(function() {
                if ($("body").hasClass("sidebar-minified")) {
                  $("body").removeClass("sidebar-minified");
                  $("#sidebar-minify i").removeClass("fa-list").addClass("fa-ellipsis-v");
                } else {
                  $("body").addClass("sidebar-minified");
                  $("#sidebar-minify i").removeClass("fa-ellipsis-v").addClass("fa-list");
                }
              });

              //dropSidebarShadow();
              //$(".sidebar").mmenu();

        }

    });
})(sx, sx.$, sx._);