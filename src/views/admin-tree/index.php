<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
/* @var $this yii\web\View */

$this->registerJs(<<<JS

function sxTreeActionsAnchor(jNode) {
    return jNode.children('.row').children('.sx-controll-node')
        .find('.sx-tree-actions-anchor').first();
}

$("body").on("dblclick", ".sx-tree-node", function(event) {
    event.preventDefault();
    event.stopPropagation();
    sxTreeActionsAnchor($(event.target).closest('.sx-tree-node')).trigger('firstAction');
    return false;
});

$("body").on("click", ".sx-first-action-trigger", function(event) {
    event.preventDefault();
    event.stopPropagation();
    sxTreeActionsAnchor($(this).closest('.sx-tree-node')).trigger('firstAction');
    return false;
});

$("body").on("contextmenu", ".sx-tree-node", function(event) {
    event.preventDefault();
    event.stopPropagation();

    var jSource = sxTreeActionsAnchor($(event.target).closest('.sx-tree-node'));
    if (!jSource.length) {
        return false;
    }

    $('.sx-tree-context-actions-anchor').each(function() {
        var jCurrent = $(this);
        if (jCurrent.data('bs.popover')) {
            try {
                jCurrent.popover('dispose');
            } catch (e) {
                jCurrent.popover('destroy');
            }
        }
        jCurrent.remove();
    });

    var jAnchor = jSource.clone();
    jAnchor
        .removeClass('sx-tree-actions-anchor')
        .addClass('sx-grid-context-actions-anchor sx-tree-context-actions-anchor')
        .attr('aria-hidden', 'true')
        .css({
            top: event.clientY,
            left: event.clientX,
            position: 'fixed'
        });

    $('body').append(jAnchor);
    jAnchor.removeClass('is-rendered');
    jAnchor.one('hidden.bs.popover', function() {
        var jCurrent = $(this);
        try {
            jCurrent.popover('dispose');
        } catch (e) {
            jCurrent.popover('destroy');
        }
        jCurrent.remove();
    });
    jAnchor.trigger('contextmenu');

    return false;
});

JS
);

?>
<div class="sx-tree-page">
    <?php $widget = \skeeks\cms\widgets\tree\CmsTreeWidget::begin([
        "models" => $models,
        "viewNodeContentFile" => '@skeeks/cms/views/admin-tree/_tree-node',

        'pjaxClass' => \skeeks\cms\modules\admin\widgets\Pjax::class,
    ]); ?>
    <?
    $canResort = \Yii::$app->user->can('cms/admin-tree/resort');
    if ($canResort) {
        \skeeks\cms\backend\widgets\sortable\assets\BackendSortableAdapterAsset::register($this);
    }

    $options = \yii\helpers\Json::encode([
        'id' => $widget->id,
        'pjaxid' => $widget->pjax->id,
        'canResort' => $canResort,
        'backendNewChild' => \skeeks\cms\helpers\UrlHelper::construct(['/cms/admin-tree/new-children'])->enableAdmin()->toString(),
        'backendResort' => \skeeks\cms\helpers\UrlHelper::construct(['/cms/admin-tree/resort'])->enableAdmin()->toString()
    ]);


    $this->registerJs(<<<JS
        (function(window, sx, $, _)
        {
            sx.createNamespace('classes.tree.admin', sx);

            sx.classes.tree.admin.CmsTreeWidget = sx.classes.Component.extend({

                _init: function()
                {
                    var self = this;
                },

                _onDomReady: function()
                {
                    var self = this;
                    /*$('.sx-tree-node').on('dblclick', function(event)
                    {
                        event.stopPropagation();
                        $(this).find(".sx-row-action:first").click();
                    });*/

                    if (this.get('canResort')) {
                        this.Sortable = sx.backend.sortable.create(
                            $(".sx-tree ul").find("ul"),
                            {
                                handle: ".sx-tree-move",
                                itemSelector: "> li",
                                forceHelperSize: true,
                                forcePlaceholderSize: true,
                                opacity: 0.5,
                                placeholderClass: "ui-state-highlight",

                                onUpdate: function(event)
                                {
                                    var Jul = event.jContainer;
                                    var newSort = [];
                                    Jul.children("li").each(function(i, element)
                                    {
                                        newSort.push($(this).data("id"));
                                    });

                                    var blocker = sx.block(Jul);

                                    var ajax = sx.ajax.preparePostQuery(
                                        self.get('backendResort'),
                                        {
                                            "ids" : newSort,
                                            "changeId" : event.jItem.data("id")
                                        }
                                    );

                                    //new sx.classes.AjaxHandlerNoLoader(ajax); //отключение глобального загрузчика
                                    new sx.classes.AjaxHandlerNotify(ajax, {
                                        'error': "Изменения не сохранились",
                                        'success': "Изменения сохранены",
                                    }); //отключение глобального загрузчика

                                    ajax.onError(function(e, data)
                                    {
                                        sx.notify.info("Подождите сейчас страница будет перезагружена");
                                        _.delay(function()
                                        {
                                            window.location.reload();
                                        }, 2000);
                                    })
                                    .onSuccess(function(e, data)
                                    {
                                        blocker.unblock();
                                    })
                                    .execute();
                                }
                            }
                        );
                    }

                    var self = this;

                    $('.add-tree-child').on('click', function()
                    {
                        var jNode = $(this);
                        sx.prompt("Введите название нового раздела", {
                            'yes' : function(e, result)
                            {
                                var blocker = sx.block(jNode);

                                var ajax = sx.ajax.preparePostQuery(
                                        self.get('backendNewChild'),
                                        {
                                            "pid" : jNode.data('id'),
                                            "Tree" : {"name" : result},
                                            "no_redirect": true
                                        }
                                );

                                //new sx.classes.AjaxHandlerNoLoader(ajax); //отключение глобального загрузчика

                                new sx.classes.AjaxHandlerNotify(ajax, {
                                    'error': "Не удалось добавить новый раздел",
                                    'success': "Новый раздел добавлен"
                                }); //отключение глобального загрузчика

                                ajax.onError(function(e, data)
                                {
                                    $.pjax.reload('#' + self.get('pjaxid'), {});
                                    /*sx.notify.info("Подождите сейчас страница будет перезагружена");
                                    _.delay(function()
                                    {
                                        window.location.reload();
                                    }, 2000);*/
                                })
                                .onSuccess(function(e, data)
                                {
                                    blocker.unblock();

                                    $.pjax.reload('#' + self.get('pjaxid'), {});
                                    /*sx.notify.info("Подождите сейчас страница будет перезагружена");
                                    _.delay(function()
                                    {
                                        window.location.reload();
                                    }, 2000);*/
                                })
                                .execute();
                            }
                        });

                        return false;
                    });

                    $('.show-at-site').on('click', function()
                    {
                        window.open($(this).attr('href'));

                        return false;
                    });
                },
            });

            new sx.classes.tree.admin.CmsTreeWidget({$options});

        })(window, sx, sx.$, sx._);
JS
    );
    ?>
    <?php $widget::end(); ?>

</div>
