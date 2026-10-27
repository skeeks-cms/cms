<?php
/**
 * Lead report: leads created in a period and what happened to them.
 *
 * @var yii\web\View $this
 * @var string $period "dd.mm.yyyy - dd.mm.yyyy"
 * @var string $source
 * @var array $report from CmsLeadReportService::build()
 */

use skeeks\cms\assets\admin\LeadReportAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsLead;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use yii\helpers\Html;
use yii\helpers\Url;

LeadReportAsset::register($this);

$formatter = Yii::$app->formatter;
$money = static fn(?float $value): string => $value === null ? '—' : $formatter->asDecimal($value, 0).' ₽';
$percent = static fn(?float $value): string => $value === null ? '—' : $formatter->asDecimal($value * 100, 0).'%';
$total = $report['total'];

$metric = static function (string $value, string $label): string {
    return Html::tag('div',
        Html::tag('strong', Html::encode($value), ['class' => 'sx-metric__value'])
        .Html::tag('span', Html::encode($label), ['class' => 'sx-metric__label']),
        ['class' => 'sx-metric']
    );
};

$bars = static function (array $rows, string $unit) use ($formatter): string {
    $max = max(array_merge([1], array_column($rows, 'count')));
    $items = '';
    foreach ($rows as $row) {
        $width = $row['count'] > 0 ? max(1, round($row['count'] / $max * 100)) : 0;
        $muted = !empty($row['notTarget']);
        $label = Html::encode($row['title']).($muted ? ' '.Html::tag('span', 'нецелевая', ['class' => 'sx-lead-report-bars__hint']) : '');
        $items .= Html::tag('li',
            Html::tag('span', $label, ['class' => 'sx-lead-report-bars__label'])
            .Html::tag('span', $width ? Html::tag('span', '', ['class' => 'sx-lead-report-bars__bar', 'style' => 'width: '.$width.'%']) : '', ['class' => 'sx-lead-report-bars__track'])
            .Html::tag('span', $formatter->asInteger($row['count']), ['class' => 'sx-lead-report-bars__value']),
            [
                'class' => 'sx-lead-report-bars__row'.($muted ? ' sx-lead-report-bars__row--muted' : ''),
                'title' => $row['title'].': '.$formatter->asInteger($row['count']).' '.$unit,
            ]
        );
    }

    return Html::tag('ul', $items, ['class' => 'sx-lead-report-bars']);
};

$groupTable = static function (array $groups, string $firstColumn, bool $hasRewards) use ($formatter, $money, $percent): string {
    $head = '<tr><th>'.Html::encode($firstColumn).'</th>'
        .'<th class="sx-lead-report__num">Сделок</th>'
        .'<th class="sx-lead-report__num">Целевых</th>'
        .'<th class="sx-lead-report__num">Успешных</th>'
        .'<th class="sx-lead-report__num">Конверсия</th>'
        .'<th class="sx-lead-report__num">Сумма сделок</th>'
        .'<th class="sx-lead-report__num">Средний чек</th>'
        .($hasRewards ? '<th class="sx-lead-report__num">Партнёрам</th>' : '')
        .'</tr>';
    $body = '';
    foreach ($groups as $group) {
        $body .= '<tr><td>'.Html::encode($group['title']).'</td>'
            .'<td class="sx-lead-report__num">'.$formatter->asInteger($group['leads']).'</td>'
            .'<td class="sx-lead-report__num">'.$formatter->asInteger($group['target']).'</td>'
            .'<td class="sx-lead-report__num">'.$formatter->asInteger($group['success']).'</td>'
            .'<td class="sx-lead-report__num">'.$percent($group['conversion']).'</td>'
            .'<td class="sx-lead-report__num">'.($group['dealCount'] ? $money($group['dealSum']) : '—').'</td>'
            .'<td class="sx-lead-report__num">'.$money($group['avgCheck']).'</td>'
            .($hasRewards ? '<td class="sx-lead-report__num">'.($group['rewardSum'] > 0 ? $money($group['rewardSum']).($group['rewardShare'] !== null ? ' · '.$percent($group['rewardShare']) : '') : '—').'</td>' : '')
            .'</tr>';
    }

    return Html::tag('div', '<table class="sx-data-table"><thead>'.$head.'</thead><tbody>'.$body.'</tbody></table>', ['class' => 'sx-data-table-wrapper']);
};
?>
<div class="sx-lead-report sx-surface-stack">
    <?php BackendSurfaceWidget::begin(['raised' => true, 'options' => ['class' => 'sx-surface--compact']]); ?>
        <?= Html::beginForm(Url::to(['report']), 'get', ['class' => 'sx-lead-report__filters']); ?>
            <label class="sx-lead-report__filter">
                <span class="sx-lead-report__filter-label">Сделки, пришедшие за период</span>
                <?= DaterangeInputWidget::widget([
                    'name' => 'period',
                    'value' => $period,
                    'options' => ['class' => 'form-control', 'placeholder' => 'Период'],
                ]); ?>
            </label>
            <label class="sx-lead-report__filter">
                <span class="sx-lead-report__filter-label">Источник</span>
                <?= Html::dropDownList('source_type', $source, ['' => 'Все источники'] + CmsLead::sources(), ['class' => 'form-control']); ?>
            </label>
            <button type="submit" class="sx-button sx-button--primary">Показать</button>
        <?= Html::endForm(); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if (!$total['leads']) : ?>
        <?php BackendSurfaceWidget::begin(['raised' => true]); ?>
            <p class="sx-lead-report__note">За этот период сделок нет. Выберите другой период или источник.</p>
        <?php BackendSurfaceWidget::end(); ?>
    <?php else : ?>
        <?php BackendSurfaceWidget::begin(['raised' => true, 'bodyFlush' => true]); ?>
            <div class="sx-metrics sx-metrics--4">
                <?= $metric($formatter->asInteger($total['leads']), 'Сделок пришло'.($total['notTarget'] ? ', нецелевых '.$formatter->asInteger($total['notTarget']) : '')); ?>
                <?= $metric($formatter->asInteger($total['success']), 'Успешных · конверсия '.$percent($total['conversion'])); ?>
                <?= $metric($total['dealCount'] ? $money($total['dealSum']) : '—', 'Сумма сделок'); ?>
                <?= $metric($money($total['avgCheck']), 'Средний чек'); ?>
            </div>
            <div class="sx-metrics sx-metrics--4">
                <?= $metric($formatter->asInteger($total['new'] + $total['inWork']), 'Ещё в работе, включая новые'); ?>
                <?= $metric($formatter->asInteger($total['rejected']), 'Отклонено целевых'); ?>
                <?= $metric($report['hasRewards'] ? $money($total['rewardSum']) : '—', 'Начислено партнёрам'); ?>
                <?= $metric($percent($total['rewardShare']), 'Доля партнёров от сделок'); ?>
            </div>
        <?php BackendSurfaceWidget::end(); ?>

        <div class="sx-lead-report__columns">
            <?php BackendSurfaceWidget::begin(['title' => 'Где сейчас сделки', 'hint' => 'Целевые сделки за период по текущему этапу', 'raised' => true]); ?>
                <?= $bars($report['funnel'], 'сделок'); ?>
            <?php BackendSurfaceWidget::end(); ?>

            <?php BackendSurfaceWidget::begin(['title' => 'Почему теряем', 'hint' => 'Отклонённые сделки за период по причинам', 'raised' => true]); ?>
                <?php if ($report['rejectReasons']) : ?>
                    <?= $bars($report['rejectReasons'], 'сделок'); ?>
                <?php else : ?>
                    <p class="sx-lead-report__note">Отклонённых сделок за период нет.</p>
                <?php endif; ?>
            <?php BackendSurfaceWidget::end(); ?>
        </div>

        <?php BackendSurfaceWidget::begin(['title' => 'По источникам', 'raised' => true]); ?>
            <?= $groupTable($report['bySource'], 'Источник', $report['hasRewards']); ?>
        <?php BackendSurfaceWidget::end(); ?>

        <?php if ($report['byPartner']) : ?>
            <?php BackendSurfaceWidget::begin(['title' => 'По партнёрам', 'raised' => true]); ?>
                <?= $groupTable($report['byPartner'], 'Партнёр', $report['hasRewards']); ?>
            <?php BackendSurfaceWidget::end(); ?>
        <?php endif; ?>

        <?php if ($report['byCampaign']) : ?>
            <?php BackendSurfaceWidget::begin(['title' => 'По рекламным кампаниям', 'hint' => 'utm_source / utm_campaign', 'raised' => true]); ?>
                <?= $groupTable($report['byCampaign'], 'Кампания', $report['hasRewards']); ?>
            <?php BackendSurfaceWidget::end(); ?>
        <?php endif; ?>

        <p class="sx-lead-report__note">
            Считаются сделки, созданные в выбранный период, в их текущем состоянии. Конверсия — успешные от целевых:
            нецелевые причины отказа (спам, дубли) отмечаются в справочнике причин и в конверсию не входят.
            Сумма сделок и средний чек — по успешным сделкам с указанной суммой.
        </p>
    <?php endif; ?>
</div>
