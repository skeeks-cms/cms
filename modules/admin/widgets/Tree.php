<?php
/**
 * TODO: Эту хрень нужно всю переписать... Но пока работает кое как. Получилась каша, и много хардкода. Изначально не те цели преследовались.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 05.03.2015
 */

namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\helpers\Action;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\controllers\helpers\ActionModel;
use skeeks\cms\modules\admin\widgets\tree\Asset;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\jui\Draggable;
use yii\jui\Sortable;
use yii\web\JsExpression;

/**
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 */
class Tree
    extends Widget
{
    /**
     * @var array
     */
    public $containerOptions =
    [
        "class" => "sx-tree"
    ];

    /**
     * @var array ноды для которых строить дерево.
     */
    public $models      = [];
    /**
     * @var string
     */
    public $selectedRequestName         = "s";
    public $openedRequestName           = "o";
    public $mode                        = "mode";

    public function init()
    {
        parent::init();
    }

    protected $_selectedTmp = [];
    protected $_openedTmp = [];
    protected $_countTmp = 0;

    /**
     * @return array
     */
    protected function _getOpenIds()
    {
        if ($fromRequest = (array) \Yii::$app->request->getQueryParam($this->openedRequestName))
        {
            $opened = array_unique($fromRequest);
        } else
        {
            $opened = array_unique(\Yii::$app->getSession()->get('cms-tree-opened', []));
            if ($opened)
            {
                \Yii::$app->response->redirect(UrlHelper::construct('cms/admin-tree/index', array_merge(\Yii::$app->request->getQueryParams(), [$this->openedRequestName => $opened]))->enableAdmin());
            }
        }

        return $opened;
    }

    /**
     * @return array
     */
    protected function _getSelectedIds()
    {
        if ($fromRequest = (array) \Yii::$app->request->getQueryParam($this->selectedRequestName))
        {
            return array_unique($fromRequest);
        }

        return [];
    }

    /**
     * @return string
     */
    protected function _getMode()
    {
        if ($mode = \Yii::$app->request->getQueryParam($this->mode))
        {
            return (string) $mode;
        }

        return '';
    }

    /**
     * TODO: учитывать приоритет
     * @return string
     */
    public function run()
    {
        $openedModels = [];

        if (\Yii::$app->request->getQueryParam('setting-open-all'))
        {
            \skeeks\cms\models\Tree::find()->where([]);
            return \Yii::$app->response->redirect(UrlHelper::construct("cms/admin-tree/index"));
        }

        if ($opened = $this->_getOpenIds())
        {
            \Yii::$app->getSession()->set('cms-tree-opened', $opened);
            $openedModels = \skeeks\cms\models\Tree::find()->where(["id" => $opened])->all();
        }

        $this->_openedTmp = $openedModels;

        $this->registerAssets();

        $addBtn = '';
        /*if ($this->_getMode() == 'multi')
        {
            $addBtn = Html::tag("div",
                    Html::a("Добавить отмеченное", '#', ['class' => 'btn btn-primary btn-sm sx-controll-btn-select'])
                    /*Html::a("Открыть все разделы", UrlHelper::construct("cms/admin-tree/index")->set('setting-open-all', 'true'), ['class' => 'btn btn-primary btn-sm']) .
                    Html::a("Закрыть все разделы", UrlHelper::construct("cms/admin-tree/index"), ['class' => 'btn btn-primary btn-sm'])
                , ['class' => "sx-container-controlls col-md-2"]);
        }*/

        return Html::tag('div',

                Html::tag("div",
                    Html::tag("div", $this->renderNodes($this->models), $this->containerOptions)
                , ['class' => "sx-container-tree col-md-12"]) . $addBtn


            ,['class' => 'row-fluid']
        );
    }


    public function renderNodes($models)
    {
        $options["item"] = function($model)
        {
            $isOpen     = false;
            $isActive   = false;

            $controller = \Yii::$app->cms->moduleCms->createControllerByID("admin-tree");
            $controller->setModel($model);

            $child = "";
            foreach ($this->_openedTmp as $activeNode)
            {
                if ($activeNode->id == $model->id)
                {
                    $isOpen = true;
                    break;
                }
            }

            if ($isOpen && $model->children)
            {
                $child = $this->renderNodes($model->children);
            }




            $openCloseLink = "";
            $currentLink = "";
            if ($model->children)
            {
                $openedIds = $this->_getOpenIds();

                if ($isOpen)
                {
                    $newOptionsOpen = [];
                    foreach ($openedIds as $id)
                    {
                        if ($id != $model->id)
                        {
                            $newOptionsOpen[] = $id;
                        }
                    }

                    $urlOptionsOpen = array_unique($newOptionsOpen);
                    $params = \Yii::$app->request->getQueryParams();
                    $params[$this->openedRequestName] = $urlOptionsOpen;

                    $currentLink = UrlHelper::construct("cms/admin-tree/index")->setData($params);
                    $openCloseLink = Html::a(
                        Html::tag("span", "" ,["class" => "glyphicon glyphicon-minus", "title" => \Yii::t('app',"Minimize")]),
                        $currentLink,
                        ['class' => 'btn btn-sm btn-default']
                    );
                } else
                {
                    $urlOptionsOpen = array_unique(array_merge($openedIds, [$model->id]));
                    $params = \Yii::$app->request->getQueryParams();
                    $params[$this->openedRequestName] = $urlOptionsOpen;
                    $currentLink = UrlHelper::construct("cms/admin-tree/index")->setData($params);
                    $openCloseLink = Html::a(
                        Html::tag("span", "" ,["class" => "glyphicon glyphicon-plus", "title" => \Yii::t('app',"Restore")]),
                        $currentLink,
                        ['class' => 'btn btn-sm btn-default']
                    );
                }

                $openCloseLink = Html::tag("div", $openCloseLink, ["class" => "sx-node-open-close"]);
            }


            if ($this->_getMode() == 'multi')
            {
                $params = \Yii::$app->request->getQueryParams();
                $isSelected = in_array($model->id, $this->_getSelectedIds()) ? true : false;
                if ($isSelected)
                {
                    $result = [];
                    foreach ($this->_getSelectedIds() as $id)
                    {
                        if ($id != $model->id)
                        {
                            $result[] = $id;
                        }
                    }
                    $params[$this->selectedRequestName] = $result;
                } else
                {
                    $params[$this->selectedRequestName] = array_unique(array_merge($this->_getSelectedIds(), [$model->id]));
                }

                $link = UrlHelper::construct("cms/admin-tree/index")->setData($params);


                $controllElement = Html::checkbox('tree_id', $isSelected, [
                    'value'     => $model->id,
                    'style'     => 'float: left; margin-left: 5px; margin-right: 5px;',
                    'onclick'   => new JsExpression(<<<JS
        sx.Tree.select("{$model->id}", "{$link}"); return false;
JS
)
                ]);


            } else if ($this->_getMode() == 'single')
            {
                $params = \Yii::$app->request->getQueryParams();
                $isSelected = in_array($model->id, $this->_getSelectedIds()) ? true : false;
                if ($isSelected)
                {
                    $params[$this->selectedRequestName] = [];
                } else
                {
                    $params[$this->selectedRequestName] = [$model->id];
                }

                $link = UrlHelper::construct("cms/admin-tree/index")->setData($params);

                $controllElement = Html::radio('tree_id', $isSelected, [
                    'value'     => $model->id,
                    'class'     => 'sx-readio',
                    'style'     => 'float: left; margin-left: 5px; margin-right: 5px;',
                    'onclick'   => new JsExpression(<<<JS
        sx.Tree.selectSingle("{$model->id}");
JS
)
                ]);

            }  else if ($this->_getMode() == 'combo')
            {
                $params = \Yii::$app->request->getQueryParams();
                $isSelected = in_array($model->id, $this->_getSelectedIds()) ? true : false;
                if ($isSelected)
                {
                    $result = [];
                    foreach ($this->_getSelectedIds() as $id)
                    {
                        if ($id != $model->id)
                        {
                            $result[] = $id;
                        }
                    }
                    $params[$this->selectedRequestName] = $result;
                } else
                {
                    $params[$this->selectedRequestName] = array_unique(array_merge($this->_getSelectedIds(), [$model->id]));
                }

                $link = UrlHelper::construct("cms/admin-tree/index")->setData($params);

                $controllElement = Html::radio('tree_id', false, [
                                    'value'     => $model->id,
                                    'class'     => 'sx-readio',
                                    'style'     => 'float: left; margin-left: 5px; margin-right: 5px;',
                                    'onclick'   => new JsExpression(<<<JS
                        sx.Tree.selectSingle("{$model->id}");
JS
                )
                    ]);


                $controllElement .= Html::checkbox('tree_id', $isSelected, [
                    'value'     => $model->id,
                    'style'     => 'float: left; margin-left: 5px; margin-right: 5px;',
                    'onclick'   => new JsExpression(<<<JS
        sx.Tree.select("{$model->id}", "{$link}"); return false;
JS
)
                ]);




            } else
            {
                $controllElement = '';
            }


            /**
             * @var $model \skeeks\cms\models\Tree
             */
            $additionalName = '';
            if ($model->level == 0)
            {
                if ($model->site)
                {
                    $additionalName = $model->site->name;
                }
            } else
            {
                if ($model->name_hidden)
                {
                    $additionalName = $model->name_hidden;
                }
            }

            $link = Html::a('<span class="glyphicon glyphicon-eye-open"></span>',
                             $model->getAbsoluteUrl(),
                             ["target" => "_blank", "class" => "btn-tree-node-controll btn btn-default btn-sm show-at-site", "title" => \Yii::t('app',"Show at site")]
                    );

            $linkMove = "";
            if ($model->level > 0)
            {
                $linkMove = Html::a('<span class="glyphicon glyphicon-move"></span>',
                             "#",
                             ["class" => "btn-tree-node-controll btn btn-default btn-sm sx-tree-move", "title" => \Yii::t('app',"Change sorting")]
                    );
            }


            $subsection = \Yii::t('app','Create subsection');

            return Html::tag("li",
                        Html::tag("div",
                            $openCloseLink .
                            $controllElement .
                            Html::tag("div",
                                Html::a($model->name . ($additionalName ? ' [' . $additionalName . ']': ''), $currentLink),
                                [
                                    "class" => "sx-label-node level-" . $model->level . " status-" . $model->active
                                ]

                            ) .

                            Html::tag("div",
                                    DropdownControllerActions::widget([
                                        "controller"    => $controller,
                                        "renderFirstAction"    => true,
                                        "containerClass"     => "dropdown pull-left",
                                        'clientOptions' =>
                                        [
                                            'pjax-id' => 'sx-pjax-tree'
                                        ]
                                    ]) .

                                    Html::tag("div",
                                        <<<HTML
                                        <a href="#" class="btn-tree-node-controll btn btn-default btn-sm add-tree-child" title="{$subsection}" data-id={$model->id}><span class="glyphicon glyphicon-plus"></span></a>
HTML
                                    ,
                                        [
                                            "class" => "pull-left sx-controll-act"
                                        ]

                                    ) .

                                    Html::tag("div", $link,
                                        [
                                            "class" => "pull-left sx-controll-act"
                                        ]
                                    ) .

                                    Html::tag("div", $linkMove,
                                        [
                                            "class" => "pull-left sx-controll-act"
                                        ]
                                    )
                                ,
                                [
                                    "class" => "sx-controll-node row"
                                ]
                            ) .

                            ($model->treeType ? Html::tag("div", $model->treeType->name, [
                                "class"     => "pull-right sx-tree-type",
                            ]) : '')

                        , ["class" => "row"])
                        . $child ,
                        [
                            "class" => "sx-tree-node " . ($isActive ? " active" : "") . ($isOpen ? " open" : ""),
                            "data-id" => $model->id,
                            "title" => "Двойной клик — переход к редактированию раздела."
                        ]
            );
        };

        $ul = Html::ul($models, $options);

        return $ul;
    }


    public function registerAssets()
    {
        Sortable::widget();


        $models     = \skeeks\cms\models\Tree::find()->where(["id" => $this->_getSelectedIds()])->all();
        $options    = Json::encode(['selected' => $models]);

        Asset::register($this->getView());
        $this->getView()->registerJs(<<<JS

        (function(window, sx, $, _)
        {
            sx.createNamespace('classes', sx);

            sx.classes.Tree = sx.classes.Component.extend({

                _init: function()
                {
                    var self = this;
                    if (sx.Window.openerWidget())
                    {
                        this._parentWidget = sx.Window.openerWidget();
                    }
                },

                _onDomReady: function()
                {
                    $('.sx-tree-node').on('dblclick', function(event)
                    {
                        event.stopPropagation();
                        $(this).find(".sx-row-action:first").click();
                    });

                    $(".sx-tree ul").find("ul").sortable(
                    {
                        cursor: "move",
                        handle: ".sx-tree-move",
                        forceHelperSize: true,
                        forcePlaceholderSize: true,
                        opacity: 0.5,
                        placeholder: "ui-state-highlight",

                        out: function( event, ui )
                        {
                            var Jul = $(ui.item).closest("ul");
                            var newSort = [];
                            Jul.children("li").each(function(i, element)
                            {
                                newSort.push($(this).data("id"));
                            });

                            var blocker = sx.block(Jul);

                            var ajax = sx.ajax.preparePostQuery(
                                "resort",
                                {
                                    "ids" : newSort,
                                    "changeId" : $(ui.item).data("id")
                                }
                            );

                            new sx.classes.AjaxHandlerNoLoader(ajax); //отключение глобального загрузчика
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
                    });

                    var self = this;
                    $('.sx-controll-btn-select').on('click', function()
                    {
                        self._parentWidget.trigger('selected', {
                            'selected': self.get('selected')
                        });

                        window.close();
                        return false;
                    });

                    $('.add-tree-child').on('click', function()
                    {
                        var jNode = $(this);
                        sx.prompt("Введите название нового раздела", {
                            'yes' : function(e, result)
                            {
                                var blocker = sx.block(jNode);

                                var ajax = sx.ajax.preparePostQuery(
                                        "new-children",
                                        {
                                            "pid" : jNode.data('id'),
                                            "Tree" : {"name" : result},
                                            "no_redirect": true
                                        }
                                );

                                new sx.classes.AjaxHandlerNoLoader(ajax); //отключение глобального загрузчика

                                new sx.classes.AjaxHandlerNotify(ajax, {
                                    'error': "Не удалось добавить новый раздел",
                                    'success': "Новый раздел добавлен"
                                }); //отключение глобального загрузчика

                                ajax.onError(function(e, data)
                                {
                                    $.pjax.reload('#sx-pjax-tree', {});
                                    /*sx.notify.info("Подождите сейчас страница будет перезагружена");
                                    _.delay(function()
                                    {
                                        window.location.reload();
                                    }, 2000);*/
                                })
                                .onSuccess(function(e, data)
                                {
                                    blocker.unblock();

                                    $.pjax.reload('#sx-pjax-tree', {});
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

                select: function(id, link)
                {
                    var selected = [];
                    $("input[type='checkbox']:checked").each(function()
                    {
                        selected.push($(this).val());
                    });

                    this.trigger("select", {
                        'selected': selected,
                        'select': id
                    });

                    _.delay(function()
                    {
                        $(".sx-tree").append();

                        $("<a>", {
                            'href':link,
                            'style':'display:none;'
                        }).append("test").appendTo($(".sx-tree")).click();

                        //window.location.href = link;
                    }, 100);
                },

                selectSingle: function(id)
                {
                    this.trigger("selectSingle", {
                        'id': id
                    });
                },

                setSingle: function(id)
                {
                    console.log('setSingle' + id);
                    var Jelement = $(".sx-tree .sx-readio[value='" + id + "']");
                    if (!Jelement.is(":checked"))
                    {
                        Jelement.click();
                    };
                },


            });

            sx.Tree = new sx.classes.Tree({$options});

        })(window, sx, sx.$, sx._);
JS
    );

        $this->getView()->registerCss(<<<CSS


CSS
);
    }
}