<?php
/**
 * GridView
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.02.2015
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\modules\admin\widgets\gridView\GridViewSettings;
use skeeks\cms\traits\HasComponentConfigFormTrait;
use skeeks\cms\traits\HasComponentDbSettingsTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\jui\Sortable;

/**
 * Class Pjax
 * @package skeeks\cms\modules\admin\widgets
 */
class GridView extends \yii\grid\GridView
{
    /**
     * @var bool
     */
    public $usePjax = true;
    /**
     * @var array
     */
    public $pjaxOptions = [];

    /**
     * @var Pjax для того чтобы потом можно было обратиться к объекту pjax.
     */
    public $pjax    = false;


    /**
     * Включить возможность сортировки (таскать tr таблицы вверх вниз)
     * @var bool
     */
    public $sortable    = false;

    public $sortableOptions    = [
        'backend' => ''
    ];


    /**
     * @var GridViewSettings
     */
    public $settings = null;

    public function init()
    {
        parent::init();

        $this->settings = new GridViewSettings([
            'namespace' => \Yii::$app->controller->action->getUniqueId()
        ]);
    }
    /**
     * Runs the widget.
     */
    public function run()
    {
        if ($this->usePjax)
        {
            $this->pjax = Pjax::begin(ArrayHelper::merge([
                'id' => 'sx-pjax-grid-' . $this->id,
            ], $this->pjaxOptions));
        }


        echo Html::a('Настройки', $this->settings->getEditUrl());

        parent::run();

        if ($this->sortable)
        {
            Sortable::widget();

            $sortableOptions = Json::encode($this->sortableOptions);
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

        if ($this->usePjax)
        {
            Pjax::end();
        }


    }

    
}