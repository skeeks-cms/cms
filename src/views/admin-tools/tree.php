<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */
/* @var $this yii\web\View */

\skeeks\cms\themes\unify\admin\assets\UnifyAdminIframeAsset::register($this);

$this->registerCss(<<<CSS

.sx-tree ul li.sx-tree-node .row .sx-controll-node
{
    display: none;
    float: left;
}

.sx-tree ul li.sx-tree-node .row .sx-controll-node .sx-btn-caret-action
{
    width: 21px;
    height: 22px;
}
.sx-tree ul li.sx-tree-node .row .sx-controll-node .btn
{
    height: 22px;
}


.sx-tree ul li.sx-tree-node .row:hover .sx-controll-node
{
    display: block;
}

.btn-tree-node-controll
{
    font-size: 8px;
}

    .sx-tree ul li.sx-tree-node .sx-controll-node
    {
        width: auto;
        float: left;
        margin-left: 10px;
        padding-top: 0px;
    }

        .sx-tree ul li.sx-tree-node .sx-controll-node > .dropdown button
        {
            font-size: 6px;
            color: #000000;
            background: white;
            padding: 2px 4px;
        }

.sx-tree-move
{
    cursor: move;
}
CSS
);
?>
<div class="col-md-12">
    <?php $widget = \skeeks\cms\widgets\tree\CmsTreeWidget::begin([
        "models" => $models,
        "viewNodeContentFile" => '@skeeks/cms/views/admin-tools/_tree-node',

        'pjaxClass' => \skeeks\cms\modules\admin\widgets\Pjax::class,
        'pjaxOptions' =>
            [
                'blockPjaxContainer' => false,
                'blockContainer' => '.sx-panel',
            ]
    ]); ?>
    <?
    $options = \yii\helpers\Json::encode([
        'id' => $widget->id,
        'pjaxid' => $widget->pjax->id,
    ]);


    $this->registerJs(<<<JS
        (function(window, sx, $, _)
        {
            sx.createNamespace('classes.tree.admin', sx);

            sx.classes.tree.admin.CmsTreeToolWidget = sx.classes.Component.extend({

                _init: function()
                {
                    var self = this;
                },

                _onDomReady: function()
                {
                    var self = this;

                    $('.show-at-site').on('click', function()
                    {
                        window.open($(this).attr('href'));
                        return false;
                    });
                },

                select: function(id)
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
                },

                selectSingle: function(id)
                {
                    this.trigger("selectSingle", {
                        'id': id
                    });
                },

                setSingle: function(id)
                {
                    var Jelement = $(".sx-tree .sx-readio[value='" + id + "']");
                    if (!Jelement.is(":checked"))
                    {
                        Jelement.click();
                    };
                },

                setSelect: function(ids)
                {
                    if (ids)
                    {
                        _.each(ids, function(id)
                        {
                            var Jelement = $(".sx-tree .sx-checkbox[value='" + id + "']");
                            if (!Jelement.is(":checked"))
                            {
                                Jelement.click();
                            };
                        });
                    }
                },
            });

            sx.Tree = new sx.classes.tree.admin.CmsTreeToolWidget({$options});

        })(window, sx, sx.$, sx._);
JS
    );
    ?>
    <?php $widget::end(); ?>

</div>