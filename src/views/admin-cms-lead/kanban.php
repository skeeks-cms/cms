<?php
/**
 * @var yii\web\View $this
 * @var array[] $columns from CmsLeadBoardService::columns()
 * @var skeeks\cms\models\CmsLeadPipeline[] $pipelines of the site; empty on a site without pipelines
 * @var skeeks\cms\models\CmsLeadPipeline|null $pipeline the pipeline of the board
 * @var array $filters source_type, no_task
 * @var int $executorId responsible employee filter, 0 for all available leads
 * @var string $q free-text search
 * @var string[] $managers employees the viewer may filter by, id => name
 * @var bool $isAdmin
 * @var array|null $setup for administrators: which lead settings exist
 */

use skeeks\cms\assets\admin\LeadKanbanAsset;
use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\crm\LeadPipelines;
use skeeks\cms\models\CmsLead;
use skeeks\cms\services\CmsLeadBoardService;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

LeadKanbanAsset::register($this);

$userId = (int)Yii::$app->user->id;
$pipelineId = $pipeline ? (int)$pipeline->id : null;
$source = (string)($filters['source_type'] ?? '');
$noTask = !empty($filters[CmsLeadBoardService::FILTER_NO_TASK]);
$isFiltered = $executorId || $q !== '' || $source !== '' || $noTask;
$total = array_sum(array_column($columns, 'total'));
// Every link of the toolbar keeps the chosen pipeline and the other filters.
$link = static function (array $change) use ($pipelineId, $executorId, $q, $source, $noTask): array {
    return array_filter(array_merge([
        'kanban',
        'pipeline' => $pipelineId,
        'executor' => $executorId ?: null,
        'q' => $q !== '' ? $q : null,
        'source' => $source !== '' ? $source : null,
        'no_task' => $noTask ? 1 : null,
    ], $change), static fn($value) => $value !== null);
};
$settingsLinks = [
    ['/cms/admin-cms-lead-pipeline', $pipelines ? 'Воронки и этапы' : 'Этапы сделок', 'Воронки продаж и шаги работы со сделкой: колонки канбана между «Неразобранное» и результатом.'],
    ['/cms/admin-cms-lead-reject-reason', 'Причины отказа', 'Список причин, из которого менеджер выбирает при отказе по сделке.'],
];
if (!$pipelines) {
    // A site without pipelines keeps the plain list of stages.
    $settingsLinks[0][0] = '/cms/admin-cms-lead-stage';
}
$missing = [];
if ($setup !== null) {
    if (!$setup['stages']) {
        $missing[] = $settingsLinks[0];
    }
    if (!$setup['rejectReasons']) {
        $missing[] = $settingsLinks[1];
    }
}
$money = static function (?float $amount): string {
    return $amount ? Yii::$app->formatter->asDecimal($amount, 0).' ₽' : '0 ₽';
};
// «+ Сделка» where it is looked for (amoCRM, Bitrix24): the standard create window, in this pipeline and column.
$createUrl = static function (?int $stageId = null) use ($pipelineId): string {
    return (string)BackendUrlHelper::createByParams(array_filter([
        '/cms/admin-cms-lead/create',
        'pipeline_id' => $pipelineId,
        'stage_id' => $stageId,
    ]))->enableEmptyLayout()->enableNoActions()->url;
};
$openWindow = static fn(string $url): string => 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $url]).').go(); return false;';
$openTotal = 0;
$openAmount = 0.0;
foreach ($columns as $column) {
    if (in_array($column['status'], [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK], true)) {
        $openTotal += $column['total'];
        $openAmount += (float)$column['amount'];
    }
}
?>
<?php if (count($pipelines) > 1) : ?>
    <nav class="sx-lead-kanban__pipelines" aria-label="Воронки">
        <?php foreach ($pipelines as $item) : ?>
            <?= Html::a(Html::encode($item->name), $link(['pipeline' => (int)$item->id]), [
                'class' => 'sx-lead-kanban__pipeline'.((int)$item->id === $pipelineId ? ' is-active' : ''),
                'aria-current' => (int)$item->id === $pipelineId ? 'page' : null,
            ]); ?>
        <?php endforeach; ?>
        <?php if ($isAdmin) : ?>
            <?= Html::a(BackendIcon::render('settings', ['size' => 14]).' Настроить', ['/cms/admin-cms-lead-pipeline'], [
                'class' => 'sx-lead-kanban__pipeline sx-lead-kanban__pipeline--setup', 'data-pjax' => '0',
            ]); ?>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<form class="sx-lead-kanban__toolbar" method="get" action="<?= Html::encode(Url::to(['kanban'])); ?>" data-sx-lead-kanban-filters>
    <?php if ($pipelineId) : ?>
        <?= Html::hiddenInput('pipeline', $pipelineId); ?>
    <?php endif; ?>
    <?= Html::a(BackendIcon::render('plus', ['size' => 16]).' Сделка', $createUrl(), [
        'class' => 'sx-button sx-button--primary sx-button--sm',
        'data-pjax' => '0',
        'onclick' => $openWindow($createUrl()),
    ]); ?>
    <div class="sx-lead-kanban__scope">
        <?= Html::a('Все сделки', $link(['executor' => null]), [
            'class' => 'sx-button sx-button--sm '.(!$executorId ? 'sx-button--primary' : 'sx-button--secondary'),
        ]); ?>
        <?= Html::a('Мои сделки', $link(['executor' => $userId]), [
            'class' => 'sx-button sx-button--sm '.($executorId === $userId ? 'sx-button--primary' : 'sx-button--secondary'),
        ]); ?>
        <?php if (count($managers) > 1) : ?>
            <?= Html::dropDownList('executor', $executorId ?: null, $managers, [
                'prompt' => 'Ответственный…',
                'class' => 'sx-lead-kanban__control sx-lead-kanban__manager'.($executorId && $executorId !== $userId ? ' is-active' : ''),
                'aria-label' => 'Ответственный сотрудник',
                'data-sx-lead-kanban-submit' => true,
            ]); ?>
        <?php elseif ($executorId) : ?>
            <?= Html::hiddenInput('executor', $executorId); ?>
        <?php endif; ?>
        <?= Html::dropDownList('source', $source !== '' ? $source : null, CmsLead::sources(), [
            'prompt' => 'Источник…',
            'class' => 'sx-lead-kanban__control sx-lead-kanban__manager'.($source !== '' ? ' is-active' : ''),
            'aria-label' => 'Источник сделки',
            'data-sx-lead-kanban-submit' => true,
        ]); ?>
        <?= Html::a('Без задач', $link(['no_task' => $noTask ? null : 1]), [
            'class' => 'sx-button sx-button--sm '.($noTask ? 'sx-button--primary' : 'sx-button--secondary'),
            'title' => 'Сделки в работе без запланированной задачи',
        ]); ?>
        <?php if ($noTask) : ?>
            <?= Html::hiddenInput('no_task', 1); ?>
        <?php endif; ?>
    </div>

    <label class="sx-lead-kanban__search">
        <?= BackendIcon::render('search', ['size' => 16]); ?>
        <?= Html::input('search', 'q', $q, [
            'class' => 'sx-lead-kanban__control',
            'placeholder' => 'Название, телефон, email',
            'aria-label' => 'Поиск сделок',
            'maxlength' => 100,
        ]); ?>
    </label>
    <?php if ($isFiltered) : ?>
        <?= Html::a('Сбросить', ['kanban', 'pipeline' => $pipelineId], ['class' => 'sx-button sx-button--sm sx-button--secondary']); ?>
    <?php endif; ?>

    <span class="sx-lead-kanban__summary" title="Открытые сделки воронки и их сумма">
        <?= $openTotal; ?> <?= LeadPipelines::plural($openTotal, 'сделка', 'сделки', 'сделок'); ?>: <?= $money($openAmount); ?>
    </span>

    <?php if ($isAdmin) : ?>
        <details class="sx-lead-kanban__settings" data-sx-lead-kanban-menu>
            <summary class="sx-button sx-button--sm sx-button--secondary" title="Настройки сделок" aria-label="Настройки сделок">
                <?= BackendIcon::render('settings', ['size' => 16]); ?>
            </summary>
            <div class="sx-lead-kanban__menu" role="menu">
                <?php foreach ($settingsLinks as [$route, $title]) : ?>
                    <?= Html::a(Html::encode($title), [$route], ['class' => 'sx-lead-kanban__menu-item', 'role' => 'menuitem', 'data-pjax' => '0']); ?>
                <?php endforeach; ?>
                <?php if (Yii::$app->has('crmSettings')) : ?>
                    <?php // «Повторы клиентов» (strict / free): the settings of the site CRM, saved for this site. ?>
                    <?php $crmSettingsUrl = Yii::$app->crmSettings->getEditUrl(); ?>
                    <?= Html::a('Повторы клиентов', $crmSettingsUrl, [
                        'class' => 'sx-lead-kanban__menu-item', 'role' => 'menuitem', 'data-pjax' => '0',
                        'onclick' => $openWindow($crmSettingsUrl),
                    ]); ?>
                <?php endif; ?>
                <?= Html::a('Отчёт по воронке', ['report', 'pipeline' => $pipelineId], ['class' => 'sx-lead-kanban__menu-item', 'role' => 'menuitem', 'data-pjax' => '0']); ?>
            </div>
        </details>
    <?php endif; ?>
</form>

<?php if ($missing) : ?>
    <div class="sx-surface sx-surface--raised sx-surface--padded sx-lead-kanban__setup">
        <div>
            <h3 class="sx-surface__title">Настройте работу со сделками</h3>
            <p class="sx-surface__hint">
                Чтобы менеджеры вели сделки по шагам и указывали понятные причины отказа, заполните настройки.
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
            Ничего не найдено. Измените запрос или <?= Html::a('сбросьте фильтр', ['kanban', 'pipeline' => $pipelineId]); ?>.
        <?php else : ?>
            Сделок пока нет. Они появятся из форм на сайте, партнёрской программы, телефонии или после
            <?= Html::a('ручного добавления', $createUrl(), ['data-pjax' => '0', 'onclick' => $openWindow($createUrl())]); ?>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="sx-lead-kanban" id="sx-lead-kanban" data-sx-lead-kanban data-move-url="<?= Html::encode(Url::to(['kanban-move'])); ?>">
    <?php foreach ($columns as $column) : ?>
        <?php
        $style = $column['stage'] && $column['stage']->color ? '--sx-lead-stage-color: '.$column['stage']->color : null;
        $isTerminal = in_array($column['key'], [CmsLeadBoardService::COLUMN_SUCCESS, CmsLeadBoardService::COLUMN_REJECTED], true);
        $columnClass = 'sx-lead-kanban__column';
        if ($column['key'] === CmsLeadBoardService::COLUMN_SUCCESS) {
            $columnClass .= ' sx-lead-kanban__column--success';
        } elseif ($column['key'] === CmsLeadBoardService::COLUMN_REJECTED) {
            $columnClass .= ' sx-lead-kanban__column--danger';
        }
        $stageId = $column['stage'] ? (int)$column['stage']->id : null;
        ?>
        <section class="<?= $columnClass; ?>" data-sx-kanban-column="<?= Html::encode($column['key']); ?>"<?= $style ? ' style="'.Html::encode($style).'"' : ''; ?>>
            <header class="sx-lead-kanban__head">
                <span class="sx-lead-kanban__head-title"><?= Html::encode($column['title']); ?></span>
                <span class="sx-lead-kanban__head-meta">
                    <span class="sx-lead-kanban__count" data-sx-kanban-count title="Сделок в колонке"><?= (int)$column['total']; ?></span>
                    <?php if ($column['amount'] !== null) : ?>
                        <span class="sx-lead-kanban__amount" title="<?= $column['status'] === CmsLead::STATUS_SUCCESS ? 'Сумма успешных сделок за '.CmsLeadBoardService::TERMINAL_DAYS.' дней' : 'Сумма открытых сделок'; ?>"><?= $money($column['amount']); ?></span>
                    <?php endif; ?>
                </span>
            </header>
            <?php if (!$isTerminal) : ?>
                <?php $url = $createUrl($stageId); ?>
                <?= Html::a(BackendIcon::render('plus', ['size' => 14]).' Быстрая сделка', $url, [
                    'class' => 'sx-lead-kanban__add',
                    'data-pjax' => '0',
                    'title' => $stageId ? 'Новая сделка сразу на этапе «'.$column['title'].'»' : 'Новая сделка',
                    'onclick' => $openWindow($url),
                ]); ?>
            <?php endif; ?>
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
