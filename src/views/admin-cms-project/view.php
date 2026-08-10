<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsProject */

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;

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

$this->registerCss(<<<CSS
.sx-project-content,
#sx-comments {
    display: grid;
    gap: var(--sx-surface-stack-gap);
}
.sx-project-overview {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.sx-project-overview-item {
    min-width: 0;
    padding: 14px;
}
.sx-project-overview-label {
    margin-bottom: 4px;
    color: var(--sx-color-text-subtle);
    font-size: 12px;
}
.sx-project-overview-value {
    color: var(--sx-color-text);
    font-weight: 600;
    overflow-wrap: anywhere;
}
.sx-project-description {
    margin: 0 0 16px;
    color: var(--sx-color-text-muted);
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
.sx-project-users {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 16px;
}
.sx-project-users-label {
    margin-top: 16px;
}
@media (max-width: 1100px) {
    .sx-project-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 700px) {
    .sx-project-overview {
        grid-template-columns: 1fr;
    }
}
CSS
);
?>

<div class="sx-project-content">
    <?php BackendSurfaceWidget::begin([
        'raised'     => true,
        'responsive' => true,
    ]); ?>
        <?php if ($model->description) : ?>
            <div class="sx-project-description"><?php echo $model->description; ?></div>
        <?php endif; ?>

        <div class="sx-project-overview">
            <div class="sx-surface sx-project-overview-item">
                <div class="sx-project-overview-label">Тип проекта</div>
                <div class="sx-project-overview-value"><?php echo $model->is_private ? 'Закрытый' : 'Открытый'; ?></div>
            </div>

            <?php if ($model->cms_company_id) : ?>
                <div class="sx-surface sx-project-overview-item">
                    <div class="sx-project-overview-label">Компания</div>
                    <div class="sx-project-overview-value">
                        <?php echo BackendEntityLink::widget([
                                'controllerId' => '/cms/admin-cms-company',
                                'modelId'      => $model->cmsCompany->id,
                                'label'        => $model->cmsCompany->name,
                                'options'      => [
                                    'class'      => 'sx-preview-card__related',
                                    'aria-label' => (string)$model->cmsCompany->name,
                                ],
                            ]); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sx-surface sx-project-overview-item">
                <div class="sx-project-overview-label">Количество задач</div>
                <div class="sx-project-overview-value">
                    <?php $count = $model->getTasks()->count(); ?>
                    <?php echo $count ? \Yii::$app->formatter->asInteger($count) : '—'; ?>
                </div>
            </div>
        </div>

        <?php if ($model->managers || $model->users) : ?>
            <div class="sx-project-overview-label sx-project-users-label">Работают с проектом</div>
            <div class="sx-project-users">
                <?php foreach ($model->managers as $manager) : ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $manager, 'isSmall' => true]); ?>
                <?php endforeach; ?>
                <?php foreach ($model->users as $user) : ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget(['cmsUser' => $user, 'isSmall' => true]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-comments',
]); ?>

    <?php BackendSurfaceWidget::begin([
        'raised'     => true,
        'responsive' => true,
    ]); ?>
        <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
            'model' => $model,
        ]); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
        'query'                => $model->getLogs()->comments(),
        'is_show_model'        => false,
        'is_show_pin_controls' => true,
    ]); ?>

<?php $pjax::end(); ?>
</div>
