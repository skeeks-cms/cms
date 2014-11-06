/*!
 *
 * Общие скрипты админки
 *
 * @date 16.10.2014
 * @copyright skeeks.com
 * @author Semenov Alexander <semenov@skeeks.com>
 */

(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    /**
     * @type {*|Function|void}
     */
    sx.classes.app.MainNav = sx.classes.Component.extend({

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
              $("#main-menu-toggle").click(function() {
                if ($("body").hasClass("sidebar-hidden")) {
                  $("body").removeClass("sidebar-hidden");
                } else {
                  $("body").addClass("sidebar-hidden");
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

    /**
     * Основной класс для управления админкой
     * @type {extend|*|Function|extend|void|extend}
     */
    sx.classes.app.Admin = sx.classes.Component.extend({

        _init: function()
        {
            this._navigation            = new sx.classes.app.MainNav(this.get("navigation"));
        },

        _onWindowReady: function()
        {
            _.delay(function()
            {
                $(".windows8").fadeOut();
                $(".sx-panel").fadeIn();
            }, 100);

        },

        _onDomReady: function()
        {
            this._initBootstrap();

            //Отключение пустых ссылок
            if (this.get("disableCetainLink", true) === true)
            {
                $('a[href^=#]').click(function (e)
                {
                    e.preventDefault()
                })
            }

            this._initWindowCloseButtons();


            $(".sx-sidebar .scrollbar-macosx").scrollbar();
        },

        /**
         * Читаем bootstrap документацию если нужно
         * @private
         */
        _initBootstrap: function()
        {
            //------------- Bootstrap tooltips -------------//
            $("[data-sx-widget=tooltip]").tooltip ({});
            $("[data-sx-widget=tooltip-r]").tooltip ({placement: 'right', container: 'body'});
            $("[data-sx-widget=tooltip-b]").tooltip ({placement: 'bottom', container: 'body'});
            $("[data-sx-widget=tooltip-l]").tooltip ({placement: 'left', container: 'body'});
            //--------------- Popovers ------------------//
            //using data-placement trigger
            $("[data-sx-widget=popover]")
                .popover()
                .click(function(e)
                {
                    e.preventDefault()
                });
        },

        /**
         * Кнопки с классом sx-admin-windowCloseButton, будут показываться только в том случае, если есть окно window.opener
         * Например в редакторе сущностей
         * @returns {sx.classes.admin.Admin}
         * @private
         */
        _initWindowCloseButtons: function()
        {
            if (window.opener)
            {
                $(".sx-admin-windowCloseButton").show();
            }

            $(".sx-admin-windowCloseButton").on("click", "body", function()
            {
                return false;
            });

            return this;
        },

        /**
         * @returns {Sidebar|*}
         */
        getSidebar: function()
        {
            return this._sidebar;
        }
    });


    /**
     * Запускаем глобальный класс админки
     * @type {Admin}
     */
    sx.app = new sx.classes.app.Admin({
        //Отключение ссылок с href="#"
        disableCetainLink: false,
        globalAjaxLoader: true

    });

})(sx, sx.$, sx._);



/**
 * @return {undefined}
 */
function startTime() {
  /** @type {Date} */
  var t2 = new Date;
  /** @type {number} */
  var front = t2.getHours();
  /** @type {number} */
  var tag = t2.getMinutes();
  /** @type {number} */
  var description = t2.getSeconds();
  tag = checkTime(tag);
  description = checkTime(description);
  /** @type {string} */
  document.getElementById("clock").innerHTML = front + ":" + tag + ":" + description;
  /** @type {number} */
  var to = setTimeout(function() {
    startTime();
  }, 500);
}
/**
 * @param {number} b
 * @return {?}
 */
function checkTime(b) {
  if (b < 10) {
    /** @type {string} */
    b = "0" + b;
  }
  return b;
}









$(function()
{
    if ($("#clock").length) {
        startTime();
      }
});



