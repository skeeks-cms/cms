<?php
/**
 * «История этапов» of a deal: every transition, who and when, the time spent
 * before the next one (CmsLeadStageHistory). The standard backend grid
 * (GridViewWidget), as the tabs «Задачи», «Счета» and «Платежи» of the card:
 * the history has no section of its own, so the grid is rendered here.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 */

use skeeks\cms\backend\widgets\GridViewWidget;
use skeeks\cms\crm\LeadPipelines;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStageHistory;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;

$pipelines = [];
foreach (CmsLeadPipeline::forSite((int)$model->cms_site_id) as $pipeline) {
    $pipelines[(int)$pipeline->id] = $pipeline->name;
}
$formatter = Yii::$app->formatter;
$durationText = static function (int $seconds): string {
    if ($seconds < 3600) {
        return max(1, (int)round($seconds / 60)).' мин';
    }
    if ($seconds < 86400) {
        return (int)round($seconds / 3600).' ч';
    }
    $days = (int)floor($seconds / 86400);

    return $days.' '.LeadPipelines::plural($days, 'день', 'дня', 'дней');
};
// Time on a stage is up to the next transition in time, whatever order the grid is sorted in.
$timeline = CmsLeadStageHistory::find()->select(['id', 'created_at'])->andWhere(['cms_lead_id' => (int)$model->id])
    ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
$nextAt = [];
foreach ($timeline as $index => $point) {
    $nextAt[(int)$point['id']] = isset($timeline[$index + 1]) ? (int)$timeline[$index + 1]['created_at'] : null;
}

echo GridViewWidget::widget([
    'dataProvider' => new ActiveDataProvider([
        'query' => CmsLeadStageHistory::find()->andWhere(['cms_lead_id' => (int)$model->id])->with('createdBy'),
        'sort' => [
            'attributes' => ['created_at', 'kind', 'created_by'],
            'defaultOrder' => ['created_at' => SORT_ASC],
        ],
        'pagination' => ['defaultPageSize' => 50],
    ]),
    'visibleColumns' => array_merge(['created_at', 'kind', 'stage'], count($pipelines) > 1 ? ['pipeline'] : [], ['created_by', 'duration']),
    'emptyText' => 'Переходов пока нет.',
    'columns' => [
        'created_at' => [
            'attribute' => 'created_at',
            'label' => 'Когда',
            'value' => static fn(CmsLeadStageHistory $row) => $formatter->asDatetime($row->created_at, 'd MMM yyyy, HH:mm'),
        ],
        'kind' => [
            'attribute' => 'kind',
            'label' => 'Что',
            'format' => 'raw',
            'value' => static fn(CmsLeadStageHistory $row) => Html::encode($row->kindName)
                .($row->is_migrated ? Html::tag('div', 'Более ранняя история не сохранялась', ['class' => 'sx-collection-cell__secondary']) : ''),
        ],
        'stage' => [
            'label' => 'Откуда → куда',
            'format' => 'raw',
            'value' => static fn(CmsLeadStageHistory $row) => ($row->from_status !== null ? Html::encode($row->fromName).' → ' : '')
                .Html::tag('strong', Html::encode($row->toName)),
        ],
        'pipeline' => [
            'label' => 'Воронка',
            'value' => static function (CmsLeadStageHistory $row) use ($pipelines) {
                $to = $pipelines[(int)$row->to_pipeline_id] ?? '—';
                if ($row->from_pipeline_id && (int)$row->from_pipeline_id !== (int)$row->to_pipeline_id) {
                    return ($pipelines[(int)$row->from_pipeline_id] ?? '—').' → '.$to;
                }

                return $to;
            },
        ],
        'created_by' => [
            'attribute' => 'created_by',
            'label' => 'Кто',
            'value' => static fn(CmsLeadStageHistory $row) => $row->createdBy ? $row->createdBy->shortDisplayName : 'Система',
        ],
        'duration' => [
            'label' => 'Пробыла на этапе',
            'format' => 'raw',
            'value' => static function (CmsLeadStageHistory $row) use ($nextAt, $model, $durationText) {
                $next = $nextAt[(int)$row->id] ?? null;
                if ($next !== null) {
                    return Html::encode($durationText($next - (int)$row->created_at));
                }
                if (!$model->isTerminal) {
                    return Html::encode($durationText(time() - (int)$row->created_at)).' '.Html::tag('span', 'и сейчас', ['class' => 'sx-collection-cell__secondary']);
                }

                return '—';
            },
        ],
    ],
]);
