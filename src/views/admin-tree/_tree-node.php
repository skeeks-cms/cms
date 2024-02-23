<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 *
 * @var \skeeks\cms\models\CmsTree $model
 */

/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
/*   */

$widget = $this->context;

$result = $model->name;
$additionalName = '';
if ($model->level == 0) {
    $site = \skeeks\cms\models\CmsSite::findOne(['id' => $model->cms_site_id]);
    if ($site) {
        $additionalName = $site->name;
    }
} else {
    if ($model->name_hidden) {
        $additionalName = $model->name_hidden;
    }
}

if ($additionalName) {
    $result .= " [{$additionalName}]";
}


if ($model->is_adult) {
    $result .= " <span class='sx-is-adult' title='Содержит контент для взрослых. Ограничения 18+' data-toggle='tooltip' class='sx-is-adult'>[18+]</span>";
}


if (!$model->isAllowIndex) {
    $result .= " <span title='Эта страница не индексируется поисковыми системами!' data-toggle='tooltip' class='sx-is-adult'>[noindex]</span>";
}

/*if ($model->is_index == 0 || $model->isRedirect || $model->isCanonical) {
    $result .= " <span title='Эта страница не попадает в карту сайта!' data-toggle='tooltip' class='sx-is-adult'>[no sitemap]</span>";
}*/

if ($model->isCanonical) {
    $result .= " <span title='У этой страницы задана атрибут rel=canonical на сатраницу: {$model->canonicalUrl}' data-toggle='tooltip' class='sx-is-adult'>[canonical]</span>";
}
?>

<div class="sx-label-node level-<?= $model->level; ?> status-<?= $model->active; ?>">

    <? if ($model->level == 0) : ?>
        <i class="fas fa-home"></i>
    <? elseif ($model->redirectTree) : ?>
        <i class="fas fa-directions" data-toggle="tooltip" title="<?= $model->redirect_code ?> редирект в раздел: <?= $model->redirectTree->fullName; ?>"></i>
    <? elseif ($model->redirect) : ?>
        <i class="fas fa-directions" data-toggle="tooltip" title="<?= $model->redirect_code ?> редирект по url: <?= $model->redirect; ?>"></i>
    <? elseif ($widget->isOpenNode($model)) : ?>
        <i class="far fa-folder-open"></i>
    <? else : ?>
        <i class="far fa-folder"></i>
    <? endif; ?>
    
    <a href="<?= $widget->getOpenCloseLink($model); ?>">
        <?= $result; ?>
    </a>

    <? if ($count = $model->getCmsContentElements()->count()) : ?>
        <small title="Сколько элементов привязано к этому разделу. Учитывается только главная привязка."><b>(<?php echo $count; ?>)</b></small>
    <? endif; ?>

    <?php if ($model->mainCmsTree) : ?>
        <small data-toggle="tooltip" title="Связан с разделом: <?= $model->mainCmsTree->fullName; ?>"><i class="fas fa-link"></i> <?= $model->mainCmsTree->name; ?></small>
    <?php endif; ?>

</div>


<!-- Possible actions -->
<div class="sx-controll-node row">
    <?
    $controller = \Yii::$app->cms->moduleCms->createControllerByID("admin-tree");
    $controller->setModel($model);
    ?>


    <?php if (\Yii::$app->user->can('cms/admin-tree/update', ['model' => $model])) : ?>
        <div class="pull-left sx-controll-act">

            <a href="#" class="btn-tree-node-controll btn btn-default btn-sm sx-first-action-trigger"
               data-id="<?= $model->id; ?>"

            >
        <span
                class="fa fa-edit"></span>
            </a>

        </div>
    <?php endif; ?>

    <?php $widget = \skeeks\cms\backend\widgets\ContextMenuControllerActionsWidget::begin([
        'actions'             => $controller->modelActions,
        'isOpenNewWindow'     => true,
        'rightClickSelectors' => ['.sx-tree-node-'.$model->id],
        'button'              => [
            'class' => 'btn btn-xs btn-default sx-btn-caret-action',
            'style' => '',
            'tag'   => 'a',
            'label' => '<i class="fa fa-caret-down"></i>',
        ],
    ]); ?>

    <div class="pull-left sx-controll-act" style="<?php echo $widget->actions ? "" : "display: none;"; ?>">
        <?php $widget::end(); ?>
    </div>

    <? /*= \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
        "actions" => $controller->modelActions,
        "renderFirstAction" => true,
        "wrapperOptions" => ['class' => "dropdown pull-left"],
        'clientOptions' =>
            [
                'pjax-id' => $widget->pjax->id
            ]
    ]); */ ?>
    <?php if (\Yii::$app->user->can('cms/admin-tree/new-children')) : ?>
        <div class="pull-left sx-controll-act">
            <a href="#" class="btn-tree-node-controll btn btn-default btn-sm add-tree-child"
               title="<?= \Yii::t('skeeks/cms', 'Create subsection'); ?>" data-id="<?= $model->id; ?>"><span
                        class="fa fa-plus"></span></a>
        </div>
    <?php endif; ?>
    <div class="pull-left sx-controll-act">
        <a href="<?= $model->absoluteUrl; ?>" target="_blank"
           class="btn-tree-node-controll btn btn-default btn-sm show-at-site"
           title="<?= \Yii::t('skeeks/cms', "Show at site"); ?>">
            <span class="fas fa-external-link-alt"></span>
        </a>
    </div>
    <?php if ($model->level > 0 && \Yii::$app->user->can('cms/admin-tree/resort')) : ?>
        <div class="pull-left sx-controll-act">
            <a href="#" class="btn-tree-node-controll btn btn-default btn-sm sx-tree-move" style="cursor: move;"
               title="<?= \Yii::t('skeeks/cms', "Change sorting"); ?>">
                <span class="fas fa-arrows-alt-v"></span>
            </a>
        </div>
    <?php endif; ?>

    <?php if ($callbackEventName = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams()->setBackendParamsByCurrentRequest()->callbackEventName) : ?>


        <?

        $this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.SelectCmsElement = sx.classes.Component.extend({

        _onDomReady: function()
        {
            $('table tr').on('dblclick', function()
            {
                $(".sx-row-action", $(this)).click();
            });
        },

        submit: function(data)
        {
            sx.notify.info("Выбрано");
            sx.Window.openerWidgetTriggerEvent('{$callbackEventName}', data);

            return this;
        }
    });

    sx.SelectCmsElement = new sx.classes.SelectCmsElement();

})(sx, sx.$, sx._);
JS
        );

        $data = \yii\helpers\ArrayHelper::merge($model->toArray(), [
            'url'      => $model->url,
            'image'    => $model->image ? $model->image->src : '',
            'fullName' => $model->fullName,
        ]);

        echo \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> '.\Yii::t('skeeks/cms',
                'Choose'), '#', [
            'class'     => 'btn btn-primary btn-xs sx-controll-act',
            'style'     => 'float: left;',
            'onclick'   => 'sx.SelectCmsElement.submit('.\yii\helpers\Json::encode($data).'); return false;',
            'data-pjax' => 0,
        ]);
        ?>
    <?php endif; ?>

</div>

<?php if ($model->treeType) : ?>
    <div class="pull-right sx-tree-type">
        <i class="fas fa-file"></i>
        <?= $model->treeType->name; ?>
    </div>
<?php endif; ?>

