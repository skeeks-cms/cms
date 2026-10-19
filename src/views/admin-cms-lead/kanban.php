<?php
/**
 * @var yii\web\View $this
 * @var array[] $columns from CmsLeadBoardService::columns()
 * @var int $executorId responsible employee filter, 0 for all available leads
 * @var string $q free-text search
 * @var string[] $managers employees the viewer may filter by, id => name
 * @var bool $isAdmin
 * @var array|null $setup for administrators: which lead settings exist
 */

use skeeks\cms\assets\admin\LeadKanbanAsset;
use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\services\CmsLeadBoardService;
use yii\helpers\Html;
use yii\helpers\Url;

LeadKanbanAsset::register($this);

$userId = (int)Yii::$app->user->id;
$isFiltered = $executorId || $q !== '';
$total = array_sum(array_column($columns, 'total'));
$settingsLinks = [
    ['/cms/admin-cms-lead-stage', 'Этапы лидов', 'Шаги работы с лидом: колонки канбана между «Новые» и результатом.'],
    ['/cms/admin-cms-lead-reject-reason', 'Причины отказа', 'Список причин, из которого менеджер выбирает при отказе по лиду.'],
];
$missing = [];
if ($setup !== null) {
    if (!$setup['stages']) {
        $missing[] = $settingsLinks[0];
    }
    if (!$setup['rejectReasons']) {
        $missing[] = $settingsLinks[1];
    }
}
?>
<form class="sx-lead-kanban__toolbar" method="get" action="<?= Html::encode(Url::to(['kanban'])); ?>" data-sx-lead-kanban-filters>
    <div class="sx-lead-kanban__scope">
        <?= Html::a('Все лиды', ['kanban', 'q' => $q ?: null], [
            'class' => 'sx-button sx-button--sm '.(!$executorId ? 'sx-button--primary' : 'sx-button--secondary'),
        ]); ?>
        <?= Html::a('Мои лиды', ['kanban', 'executor' => $userId, 'q' => $q ?: null], [
            'class' => 'sx-button sx-button--sm '.($executorId === $userId ? 'sx-button--primary' : 'sx-button--secondary'),
        ]); ?>
        <?php if (count($managers) > 1) : ?>
            <?= Html::dropDownList('executor', $executorId !== $userId ? ($executorId ?: null) : null, $managers, [
                'prompt' => 'Лиды сотрудника…',
                'class' => 'sx-lead-kanban__control sx-lead-kanban__manager'.($executorId && $executorId !== $userId ? ' is-active' : ''),
                'aria-label' => 'Ответственный сотрудник',
                'data-sx-lead-kanban-submit' => true,
            ]); ?>
        <?php elseif ($executorId) : ?>
            <?= Html::hiddenInput('executor', $executorId); ?>
        <?php endif; ?>
    </div>

    <label class="sx-lead-kanban__search">
        <?= BackendIcon::render('search', ['size' => 16]); ?>
        <?= Html::input('search', 'q', $q, [
            'class' => 'sx-lead-kanban__control',
            'placeholder' => 'Название, телефон, email',
            'aria-label' => 'Поиск лидов',
            'maxlength' => 100,
        ]); ?>
    </label>
    <?php if ($isFiltered) : ?>
        <?= Html::a('Сбросить', ['kanban'], ['class' => 'sx-button sx-button--sm sx-button--secondary']); ?>
    <?php endif; ?>

    <?php if ($isAdmin) : ?>
        <details class="sx-lead-kanban__settings" data-sx-lead-kanban-menu>
            <summary class="sx-button sx-button--sm sx-button--secondary" title="Настройки лидов" aria-label="Настройки лидов">
                <?= BackendIcon::render('settings', ['size' => 16]); ?>
            </summary>
            <div class="sx-lead-kanban__menu" role="menu">
                <?php foreach ($settingsLinks as [$route, $title]) : ?>
                    <?= Html::a(Html::encode($title), [$route], ['class' => 'sx-lead-kanban__menu-item', 'role' => 'menuitem', 'data-pjax' => '0']); ?>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</form>

<?php if ($missing) : ?>
    <div class="sx-surface sx-surface--raised sx-surface--padded sx-lead-kanban__setup">
        <div>
            <h3 class="sx-surface__title">Настройте работу с лидами</h3>
            <p class="sx-surface__hint">
                Чтобы менеджеры вели лиды по шагам и указывали понятные причины отказа, заполните настройки.
                Пока они пустые, канбан работает в упрощённом виде.
            </p>
        </div>
        <ul class="sx-lead-kanban__setup-list">
            <?php foreach ($missing as [$route, $title, $description]) : ?>
                <li>
                    <?= Html::a(Html::encode($title), [$route], ['class' => 'sx-button sx-button--sm sx-button--primary', 'data-pjax' => '0']); ?>
                    <span><?= Html::encode($description); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$total) : ?>
    <div class="sx-lead-kanban__empty">
        <?php if ($isFiltered) : ?>
            Ничего не найдено. Измените запрос или <?= Html::a('сбросьте фильтр', ['kanban']); ?>.
        <?php else : ?>
            Лидов пока нет. Они появятся из форм на сайте, партнёрской программы, телефонии или после
            <?= Html::a('ручного добавления', ['create'], ['data-pjax' => '0']); ?>.
        <?php endif; ?>
    </div>
<?php endif; ?>

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
