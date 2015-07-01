<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
namespace skeeks\cms\modules\admin\traits;
use yii\helpers\Json;
use yii\jui\Sortable;
use yii\widgets\Pjax;

/**
 *
 * Class GridViewSortableTrait
 * @package skeeks\cms\modules\admin\traits
 */
trait GridViewSortableTrait
{
    /**
     * Включить возможность сортировки (таскать tr таблицы вверх вниз)
     * @var bool
     */
    public $sortable            = false;

    public $sortableOptions     = [
        'backend' => ''
    ];

    public function registerSortableJs()
    {
        $pjaxId = '';
        if (property_exists($this, 'pjax'))
        {
            $pjax = $this->pjax;
            if ($pjax && ($pjax instanceof Pjax))
            {
                $pjaxId = $pjax->id;
            }
        }

        if ($this->sortable)
        {
            Sortable::widget();

            $options = $this->sortableOptions;
            $options['pjaxId'] = $pjaxId;

            $sortableOptions = Json::encode($options);
            $this->view->registerCss(<<<Css
            table.sx-sortable tbody>tr
            {
                cursor: move;
            }
Css
        );
            $this->view->registerJs(<<<JS
            (function(sx, $, _)
            {
                sx.classes.TableSortable = sx.classes.Widget.extend({

                    _init: function()
                    {},

                    _onDomReady: function()
                    {
                        var self = this;
                        this.Jtable = this.getWrapper().find('table');
                        this.Jtable.addClass('sx-sortable');
                        $('tbody', this.Jtable).sortable({

                            out: function( event, ui )
                            {
                                var Jtbody = $(ui.item).closest("tbody");
                                var newSort = [];
                                Jtbody.children("tr").each(function(i, element)
                                {
                                    newSort.push($(this).data("key"));
                                });

                                var blocker = sx.block(self.getWrapper());

                                var ajax = sx.ajax.preparePostQuery(
                                    self.get('backend'),
                                    {
                                        "keys" : newSort,
                                        "changeKey" : $(ui.item).data("key")
                                    }
                                );

                                new sx.classes.AjaxHandlerNoLoader(ajax); //отключение глобального загрузчика
                                new sx.classes.AjaxHandlerNotifyErrors(ajax, {
                                    'error': "Изменения не сохранились",
                                    'success': "Изменения сохранены",
                                }); //отключение глобального загрузчика

                                ajax.onError(function(e, data)
                                {
                                    if (self.get('pjaxId'))
                                    {
                                        $.pjax.reload($("#" + self.get('pjaxId')), {});
                                    }

                                    blocker.unblock();
                                    //sx.notify.info("Подождите сейчас страница будет перезагружена");
                                    _.delay(function()
                                    {
                                        //window.location.reload();
                                        //blocker.unblock();
                                    }, 2000);
                                })
                                .onSuccess(function(e, data)
                                {
                                    if (self.get('pjaxId'))
                                    {
                                        $.pjax.reload($("#" + self.get('pjaxId')), {});
                                    }

                                    var response = data.response;
                                    if (response.success === false)
                                    {
                                        sx.notify.error(response.message);
                                    } else
                                    {
                                        sx.notify.success(response.message);
                                    }

                                    blocker.unblock();
                                })
                                .execute();
                            }

                        });
                    },

                    _onWindowReady: function()
                    {}
                });

                new sx.classes.TableSortable('#{$this->id}', {$sortableOptions});
            })(sx, sx.$, sx._);
JS
);
        }
    }
}
