<?php
/* @var $this yii\web\View */
/* @var $dashboard skeeks\cms\models\CmsDashboard */

$this->title = $dashboard->name . " / " . \Yii::t('app','Dashboard');


$this->registerCss(<<<CSS
.sx-dashboard-head
{
    padding: 10px 0;
    margin-bottom: 10px;
    border-left: 1px solid rgba(255, 255, 255, 0.46);
}

.sx-dashboard table tr td
{
    vertical-align: top;
}

CSS
);

$sortableString = [];
?>
<div class="col-md-12 sx-dashboard">

    <? echo $this->render('_head', [
        'dashboard' => $dashboard
    ]); ?>

    <div class="row sx-dashboard-body">
        <div class="col-lg-12 col-md-12">
            <? if (!$dashboard->cmsDashboardWidgets) : ?>

                <?=
                    yii\bootstrap\Alert::widget([
                        'options' => [
                          'class' => 'alert-info',
                      ],
                      'body' => \yii\helpers\Html::tag("h1", \Yii::t('app','Welcome! You are in the site management system.')),
                    ]);
                ?>

            <? else : ?>


                <table id="sx-dashboard-table">
                    <tr>
                        <? for($i = 1; $i <= $dashboard->columns; $i++) : ?>
                            <?
                            $sortableString[] = "#sx-column-" . $i;
                            ?>
                            <td style="width: <? echo round(100/$dashboard->columns); ?>%;" id="sx-column-<?= $i; ?>" class="sx-columns" data-column="<?= $i; ?>">
                                <? $widgets = $dashboard->getCmsDashboardWidgets()->andWhere(['cms_dashboard_column' => $i])->all(); ?>
                                <? if ($widgets) : ?>

                                    <? foreach($widgets as $cmsDashboardWidget) : ?>
                                        <? \skeeks\cms\modules\admin\widgets\AdminPanelWidget::begin([
                                            'name'      => $cmsDashboardWidget->name,

                                            'options' =>
                                            [
                                                'class' => 'sx-dashboard-widget',
                                                'data'      =>
                                                [
                                                    'id' => $cmsDashboardWidget->id
                                                ],
                                            ],
                                        ]); ?>
                                            <?= $cmsDashboardWidget->widget->run(); ?>
                                        <? \skeeks\cms\modules\admin\widgets\AdminPanelWidget::end(); ?>
                                    <? endforeach; ?>

                                <? endif; ?>
                            </td>
                            <? if ($dashboard->columns > 1 && $i != $dashboard->columns) : ?>
                                <td width="15"></td>
                            <? endif; ?>
                        <? endfor; ?>
                    </tr>
                </table>

            <? endif; ?>

        </div>
    </div>
</div>


<?

\yii\jui\Sortable::widget();

$sortableString = implode(', ', $sortableString);

$jsonData = \yii\helpers\Json::encode([
    'model'             => $dashboard,
    'sortableSelector'  => $sortableString,
]);

$this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.classes.Dashboard = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            this.bind('change', function(e, data)
            {
                self.save();
            });
        },

        _onDomReady: function()
        {
            this._initSortable();
        },

        getData: function()
        {

        },

        save: function()
        {

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
        },

        _initSortable: function()
        {
            var self = this;

            $(self.get('sortableSelector')).sortable(
            {
                connectWith: ".sx-columns",
                cursor: "move",
                forceHelperSize: true,
                forcePlaceholderSize: true,
                //delay: 150,
                opacity: 0.5,
                placeholder: "ui-state-highlight",
                stop: function( event, ui )
                {
                    self.trigger('change', {
                        'event' : event,
                        'ui' : ui,
                    });
                }

            }).disableSelection();
        }
    });

    sx.Dashboard = new sx.classes.Dashboard({$jsonData});
})(sx, sx.$, sx._);


JS
);

?>