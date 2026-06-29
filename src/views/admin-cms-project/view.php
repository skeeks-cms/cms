<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsProject */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$quickAccessItems = [];
$makeQuickAccessActionUrl = function ($route, $id) {
    return (string) \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
        $route,
        'pk' => $id,
    ])->enableEmptyLayout()->enableNoActions()->url;
};
$makeQuickAccessImageUrl = function ($model) {
    if ($model && $model->cmsImage) {
        return (string) \Yii::$app->imaging->thumbnailUrlOnRequest($model->cmsImage->src, new \skeeks\cms\components\imaging\filters\Thumbnail([
            'w' => 80,
            'h' => 80,
            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
        ]), '', true);
    }

    return null;
};

if ($model->cmsCompany) {
    $quickAccessItems[] = [
        'type'   => 'companies',
        'id'     => (int) $model->cmsCompany->id,
        'name'   => (string) $model->cmsCompany->name,
        'url'    => \yii\helpers\Url::to(['/cms/admin-cms-company/view', 'pk' => $model->cmsCompany->id]),
        'action' => $makeQuickAccessActionUrl('/cms/admin-cms-company/view', $model->cmsCompany->id),
        'image'  => $makeQuickAccessImageUrl($model->cmsCompany),
    ];
}

$quickAccessItems[] = [
    'type'   => 'projects',
    'id'     => (int) $model->id,
    'name'   => (string) $model->name,
    'url'    => \yii\helpers\Url::to(['/cms/admin-cms-project/view', 'pk' => $model->id]),
    'action' => $makeQuickAccessActionUrl('/cms/admin-cms-project/view', $model->id),
    'image'  => $makeQuickAccessImageUrl($model),
];

$quickAccessItemsJson = \yii\helpers\Json::encode($quickAccessItems);
$this->registerJs(<<<JS
(function(items) {
    var attempts = 0;
    var item = items[items.length - 1];
    var mountFavorite = function() {
        attempts++;
        var windows = [window, window.parent, window.top, window.opener];
        var target = null;

        for (var w = 0; w < windows.length; w++) {
            try {
                var candidate = windows[w];
                if (!candidate || !candidate.sx || !candidate.sx.Project || !candidate.sx.Project.quickAccessToggleFavorite) {
                    continue;
                }

                if (candidate.document && candidate.document.querySelector('[data-sx-quick-access-edge-favorites]')) {
                    target = candidate;
                    break;
                }

                if (!target) {
                    target = candidate;
                }
            } catch (e) {
            }
        }

        var \$title = $('h1').first();
        if (!item || !target || !\$title.length) {
            if (attempts < 10) {
                setTimeout(mountFavorite, 300);
            }
            return false;
        }

        var \$button = \$title.find('[data-sx-quick-access-favorite]').first();
        var isNewButton = !\$button.length;
        if (isNewButton) {
            \$button = $('<button type="button" class="sx-quick-access-favorite-btn" data-sx-quick-access-favorite title="Добавить в избранное"><i class="far fa-star"></i></button>');
        }
        \$button.attr('data-sx-quick-access-item', JSON.stringify(item));
        \$button.attr('data-sx-quick-access-external', '1');
        var update = function(active) {
            if (typeof active === 'undefined') {
                active = false;
                try {
                    active = target.sx.Project.quickAccessIsFavorite(item);
                } catch (e) {
                }
            }

            \$button.toggleClass('is-active', active);
            \$button.attr('title', active ? 'Убрать из избранного' : 'Добавить в избранное');
            \$button.find('i').toggleClass('fas', active).toggleClass('far', !active);
        };

        \$button.off('click.sxQuickAccessFavorite').on('click.sxQuickAccessFavorite', function(e) {
            e.preventDefault();
            e.stopPropagation();
            update(target.sx.Project.quickAccessToggleFavorite(item));
        });

        if (isNewButton) {
            \$title.append(\$button);
        }
        update();
        return true;
    };

    mountFavorite();
})({$quickAccessItemsJson});
JS
);
?>


    <div class="sx-block">
        <?php if ($model->description) : ?>
            <div style="margin-bottom: 1rem;"><?php echo $model->description; ?></div>
        <?php endif; ?>


        <div class="sx-properties-wrapper sx-columns-1">
            <ul class="sx-properties">
                <!--<li>
                <span class="sx-properties--name">
                    Создан
                </span>
                    <span class="sx-properties--value">
                    <?php /*echo \Yii::$app->formatter->asDate($model->created_at) */ ?>
                </span>
                </li>-->


                <li>
                <span class="sx-properties--name">
                    Тип проекта
                </span>
                    <span class="sx-properties--value">
    
                    <?php if ($model->is_private) : ?>
                        Закрытый
                    <?php else : ?>
                        Открытый
                    <?php endif; ?>
    
                </span>
                </li>

                <?php if ($model->cms_company_id) : ?>
                <li>
                    <span class="sx-properties--name">
                        Компания
                    </span>
                    <span class="sx-properties--value">

                            <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                'controllerId'            => '/cms/admin-cms-company',
                                'modelId'                 => $model->cmsCompany->id,
                                'isRunFirstActionOnClick' => true,
                                'options'                 => [
                                    'class' => 'sx-dashed',
                                    'style' => 'cursor: pointer; border-bottom: 1px dashed;',
                                ],
                            ]); ?>
                            <?php echo $model->cmsCompany->name; ?>
                            <?php $widget::end(); ?>
                    </span>
                </li>
                <?php endif; ?>

                <?php if ($model->managers) : ?>
                    <li>
                <span class="sx-properties--name">
                    Работают с проектом
                </span>
                        <span class="sx-properties--value">
                    <?php foreach ($model->managers as $manager) : ?>
                        <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $manager, "isSmall" => true]); ?>
                    <?php endforeach; ?>
                </span>
                    </li>
                <?php endif; ?>

                <?php if ($model->users) : ?>
                    <li>
                <span class="sx-properties--name">
                    Работают с проектом
                </span>
                        <span class="sx-properties--value">
                    <?php foreach ($model->users as $user) : ?>
                        <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget(['cmsUser' => $user, "isSmall" => true]); ?>
                    <?php endforeach; ?>
                </span>
                    </li>
                <?php endif; ?>


                <li>
                <span class="sx-properties--name">
                    Количество задач
                </span>
                    <span class="sx-properties--value">
    
                    <?php
                    $count = $model->getTasks()->count();
                    if ($count) : ?>
                        <?php echo \Yii::$app->formatter->asInteger($count); ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
    
                </span>
                </li>
            </ul>
        </div>


    </div>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-comments',
]); ?>

    <div class="row">
        <div class="col-12">
            <div class="sx-block">
                <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
                    'model' => $model,
                ]); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                'query'         => $model->getLogs()->comments(),
                'is_show_model' => false,
                'is_show_pin_controls' => true,
            ]); ?>
        </div>
    </div>

<?php $pjax::end(); ?>
