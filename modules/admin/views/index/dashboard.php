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

CSS
);

$sortableString = [];
?>
<div class="col-md-12">

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


                <table>
                    <tr>
                        <? for($i = 1; $i <= $dashboard->columns; $i++) : ?>
                            <?
                            $sortableString[] = "#sx-column-" . $i;
                            ?>
                            <td style="width: <? echo round(100/$dashboard->columns); ?>%;" id="sx-column-<?= $i; ?>" class="sx-columns">
                                <? $widgets = $dashboard->getCmsDashboardWidgets()->andWhere(['cms_dashboard_column' => $i])->all(); ?>
                                <? if ($widgets) : ?>

                                    <? foreach($widgets as $cmsDashboardWidget) : ?>
                                        <? \skeeks\cms\modules\admin\widgets\AdminPanelWidget::begin([
                                            'name'      => $cmsDashboardWidget->name,
                                            'cssClass'  => 'sx-dashboard-widget',
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
$this->registerJs(<<<JS
$("{$sortableString}").sortable(
{
    connectWith: ".sx-columns",
    cursor: "move",
    forceHelperSize: true,
    forcePlaceholderSize: true,
    //delay: 150,
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
}).disableSelection();;
JS
);

?>