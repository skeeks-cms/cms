<?php
/**
 * «История этапов» of a deal: every transition, who and when, the time spent
 * before the next one (CmsLeadStageHistory).
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\LeadPipelines;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStageHistory;
use yii\helpers\Html;

/** @var CmsLeadStageHistory[] $rows */
$rows = $model->getStageHistory()->with('createdBy')->all();
$pipelines = [];
foreach (CmsLeadPipeline::forSite((int)$model->cms_site_id) as $pipeline) {
    $pipelines[(int)$pipeline->id] = $pipeline->name;
}
$showPipeline = count($pipelines) > 1;
$formatter = Yii::$app->formatter;
$duration = static function (int $seconds): string {
    if ($seconds < 3600) {
        return max(1, (int)round($seconds / 60)).' мин';
    }
    if ($seconds < 86400) {
        return (int)round($seconds / 3600).' ч';
    }
    $days = (int)floor($seconds / 86400);

    return $days.' '.LeadPipelines::plural($days, 'день', 'дня', 'дней');
};
?>
<?php BackendSurfaceWidget::begin([
    'title' => 'История этапов',
    'titleTag' => 'h2',
    'hint' => 'Каждый переход сделки: откуда и куда, кто и когда, сколько сделка пробыла на этапе. По этим записям считается отчёт по воронке.',
    'raised' => true,
    'responsive' => true,
]); ?>
    <?php if (!$rows) : ?>
        <p>Переходов пока нет.</p>
    <?php else : ?>
        <table class="table sx-table">
            <thead>
            <tr>
                <th>Когда</th>
                <th>Что</th>
                <th>Откуда → куда</th>
                <?php if ($showPipeline) : ?><th>Воронка</th><?php endif; ?>
                <th>Кто</th>
                <th>Пробыла на этапе</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $index => $row) : ?>
                <?php $next = $rows[$index + 1] ?? null; ?>
                <tr>
                    <td><?= $formatter->asDatetime($row->created_at, 'd MMM yyyy, HH:mm'); ?></td>
                    <td>
                        <?= Html::encode($row->kindName); ?>
                        <?php if ($row->is_migrated) : ?>
                            <div class="sx-collection-cell__secondary">Более ранняя история не сохранялась</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row->from_status !== null) : ?>
                            <?= Html::encode($row->fromName); ?> →
                        <?php endif; ?>
                        <strong><?= Html::encode($row->toName); ?></strong>
                    </td>
                    <?php if ($showPipeline) : ?>
                        <td>
                            <?php if ($row->from_pipeline_id && (int)$row->from_pipeline_id !== (int)$row->to_pipeline_id) : ?>
                                <?= Html::encode($pipelines[(int)$row->from_pipeline_id] ?? '—'); ?> →
                            <?php endif; ?>
                            <?= Html::encode($pipelines[(int)$row->to_pipeline_id] ?? '—'); ?>
                        </td>
                    <?php endif; ?>
                    <td><?= $row->createdBy ? Html::encode($row->createdBy->shortDisplayName) : 'Система'; ?></td>
                    <td>
                        <?php if ($next) : ?>
                            <?= $duration((int)$next->created_at - (int)$row->created_at); ?>
                        <?php elseif (!$model->isTerminal) : ?>
                            <?= $duration(time() - (int)$row->created_at); ?> <span class="sx-collection-cell__secondary">и сейчас</span>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php BackendSurfaceWidget::end(); ?>
