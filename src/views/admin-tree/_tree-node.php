<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.12.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
/* @var $model \skeeks\cms\models\CmsTree */
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

?>

<div class="sx-label-node level-<?= $model->level; ?> status-<?= $model->active; ?>">


    <a href="<?= $widget->getOpenCloseLink($model); ?>">
        <?= $result; ?>
    </a>
    <?php if ($model->redirect || $model->redirect_tree_id) : ?>
        →
    <?php endif; ?>
    <?php if ($model->redirect) : ?>
        <?= $model->redirect; ?>
    <?php endif; ?>
    <?php if ($model->redirectTree) : ?>
        <?php if ($parents = $model->redirectTree->parents) : ?>
            <?
            $root = $parents[0];
            unset($parents[0]);
            /**
             * @var \skeeks\cms\models\CmsTree $root
             */
            $names[] = $root->site->name;
            if ($parents) {
                $names = \yii\helpers\ArrayHelper::merge($names,
                    \yii\helpers\ArrayHelper::map($parents, 'name', 'name'));
            }
            $names[] = $model->redirectTree->name;
            echo implode(" / ", $names);
            ?>
        <?php else
            : ?>
            <?
            $names[] = $model->redirectTree->site->name;

            echo implode(" / ", $names);
            ?>
        <?php endif; ?>

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
                <span class="fa fa-arrows"></span>
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
            if (window.opener)
            {
                if (window.opener.sx)
                {
                    window.opener.sx.EventManager.trigger('{$callbackEventName}', data);
                    return this;
                }
            } else if (window.parent)
            {
                if (window.parent.sx)
                {
                    window.parent.sx.EventManager.trigger('{$callbackEventName}', data);
                    return this;
                }
            }

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
        <?= $model->treeType->name; ?>
    </div>
<?php endif; ?>

