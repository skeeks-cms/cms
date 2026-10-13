<?php
/**
 * @var yii\web\View $this
 * @var array[] $columns from CmsLeadBoardService::columns()
 * @var bool $mine
 */

use skeeks\cms\assets\admin\LeadKanbanAsset;
use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\CmsLeadBoardService;
use yii\helpers\Html;
use yii\helpers\Url;

LeadKanbanAsset::register($this);

$isAdmin = Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
$hasStages = (bool)array_filter($columns, static fn(array $column) => $column['stage'] !== null);
?>
<div class="sx-lead-kanban__toolbar">
    <?= Html::a('Все лиды', ['kanban'], [
        'class' => 'sx-button sx-button--sm '.($mine ? 'sx-button--secondary' : 'sx-button--primary'),
    ]); ?>
    <?= Html::a('Мои лиды', ['kanban', 'mine' => 1], [
        'class' => 'sx-button sx-button--sm '.($mine ? 'sx-button--primary' : 'sx-button--secondary'),
    ]); ?>
    <?php if ($isAdmin) : ?>
        <?= Html::a(BackendIcon::render('settings', ['size' => 16]).' Этапы', ['/cms/admin-cms-lead-stage'], [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-pjax' => '0',
        ]); ?>
    <?php endif; ?>
    <span class="sx-lead-kanban__hint">
        <?php if (!$hasStages && $isAdmin) : ?>
            Добавьте этапы, чтобы разбить «В работе» на шаги.
        <?php else : ?>
            Перетащите карточку в другую колонку. Для завершения откроется форма с результатом или причиной отказа.
        <?php endif; ?>
    </span>
</div>

<div class="sx-lead-kanban" id="sx-lead-kanban" data-sx-lead-kanban data-move-url="<?= Html::encode(Url::to(['kanban-move'])); ?>">
    <?php foreach ($columns as $column) : ?>
        <?php
        $style = $column['stage'] && $column['stage']->color ? '--sx-lead-stage-color: '.$column['stage']->color : null;
        $isTerminal = in_array($column['key'], [CmsLeadBoardService::COLUMN_SUCCESS, CmsLeadBoardService::COLUMN_REJECTED], true);
        ?>
        <section class="sx-lead-kanban__column" data-sx-kanban-column="<?= Html::encode($column['key']); ?>"<?= $style ? ' style="'.Html::encode($style).'"' : ''; ?>>
            <header class="sx-lead-kanban__head">
                <span><?= Html::encode($column['title']); ?></span>
                <span class="sx-lead-kanban__count" data-sx-kanban-count><?= (int)$column['total']; ?></span>
            </header>
            <div class="sx-lead-kanban__list" data-sx-kanban-list>
                <?php foreach ($column['leads'] as $lead) : ?>
                    <?= $this->render('_kanban-card', ['model' => $lead]); ?>
                <?php endforeach; ?>
            </div>
            <?php if ($column['total'] > count($column['leads'])) : ?>
                <div class="sx-lead-kanban__more">
                    Показаны <?= count($column['leads']); ?> из <?= (int)$column['total']; ?><?= $isTerminal ? ' за '.CmsLeadBoardService::TERMINAL_DAYS.' дней' : ''; ?>
                </div>
            <?php elseif ($isTerminal) : ?>
                <div class="sx-lead-kanban__more">За последние <?= CmsLeadBoardService::TERMINAL_DAYS; ?> дней</div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
