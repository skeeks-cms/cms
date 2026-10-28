<?php
/**
 * «Воронка» of the lead report by the history of transitions (П2б).
 *
 * @var yii\web\View $this
 * @var array $funnel from CmsLeadFunnelReportService::build()
 * @var skeeks\cms\models\CmsLeadPipeline|null $pipeline
 * @var string $period
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\LeadPipelines;
use yii\helpers\Html;

$formatter = Yii::$app->formatter;
$money = static fn(?float $value): string => !$value ? '—' : $formatter->asDecimal($value, 0).' ₽';
$percent = static fn(?float $value): string => $value === null ? '—' : $formatter->asDecimal($value * 100, 0).'%';
$duration = static function (?float $seconds): string {
    if ($seconds === null) {
        return '—';
    }
    if ($seconds < 3600) {
        return max(1, (int)round($seconds / 60)).' мин';
    }
    if ($seconds < 86400) {
        return (int)round($seconds / 3600).' ч';
    }
    $days = round($seconds / 86400, 1);

    return Yii::$app->formatter->asDecimal($days, $days < 10 ? 1 : 0).' дн.';
};
$metric = static function (string $value, string $label): string {
    return Html::tag('div',
        Html::tag('strong', Html::encode($value), ['class' => 'sx-metric__value'])
        .Html::tag('span', Html::encode($label), ['class' => 'sx-metric__label']),
        ['class' => 'sx-metric']
    );
};
$groupTable = static function (array $rows, string $first) use ($formatter, $money, $percent): string {
    $body = '';
    foreach ($rows as $row) {
        $body .= '<tr><td>'.Html::encode($row['title']).'</td>'
            .'<td class="sx-lead-report__num">'.$formatter->asInteger($row['entered']).'</td>'
            .'<td class="sx-lead-report__num">'.$formatter->asInteger($row['taken']).'</td>'
            .'<td class="sx-lead-report__num">'.$formatter->asInteger($row['success']).'</td>'
            .'<td class="sx-lead-report__num">'.$percent($row['conversion']).'</td>'
            .'<td class="sx-lead-report__num">'.$money($row['amount']).'</td></tr>';
    }

    return Html::tag('div', '<table class="sx-data-table"><thead><tr><th>'.Html::encode($first).'</th>'
        .'<th class="sx-lead-report__num">Поступило</th><th class="sx-lead-report__num">В работу</th>'
        .'<th class="sx-lead-report__num">Успешно</th><th class="sx-lead-report__num">Конверсия</th>'
        .'<th class="sx-lead-report__num">Сумма</th></tr></thead><tbody>'.$body.'</tbody></table>', ['class' => 'sx-data-table-wrapper']);
};
$sales = $funnel['sales'];
$max = max(1, (int)($funnel['steps'][0]['reached'] ?? 1));
?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Воронка'.($pipeline ? ' «'.$pipeline->name.'»' : '').' по истории переходов',
    'titleTag' => 'h2',
    'raised' => true,
]); ?>
    <details class="sx-lead-report__rules">
        <summary>Как считается</summary>
        <ul>
            <li>Воронка, конверсия, источники и ответственные — по сделкам, которые <strong>попали в воронку в выбранный период</strong>. Их дальнейшее движение учитывается до сегодняшнего дня.</li>
            <li>Сделка проходит этап, если побывала на нём или дальше. Считается по истории переходов, а не по текущей колонке канбана.</li>
            <li>Конверсия — успешные из взятых в работу. «Неразобранное», спам, повторы, поддержка и нецелевые отказы в неё не входят.</li>
            <li>Переоткрытая сделка считается один раз — по последнему результату.</li>
            <li>Первые и повторные продажи — по <strong>дате закрытия</strong> в периоде. Повторная — если у этой компании (или человека) уже была успешная сделка раньше.</li>
            <li>Сумма — согласованная сумма сделки, один раз на сделку. Счета и оплаты не складываются.</li>
            <li>Время на этапе — только по настоящим переходам.<?php if ($funnel['migratedAt']) : ?> До <?= $formatter->asDate($funnel['migratedAt'], 'd MMMM yyyy'); ?> история этапов не сохранялась: у старых сделок известен только этап на эту дату.<?php endif; ?></li>
        </ul>
    </details>

    <div class="sx-metrics sx-metrics--4">
        <?= $metric($formatter->asInteger($funnel['entered']), 'Поступило в воронку'); ?>
        <?= $metric($formatter->asInteger($funnel['taken']), 'Взято в работу'); ?>
        <?= $metric($formatter->asInteger($funnel['success']).' · '.$percent($funnel['conversion']), 'Успешно · конверсия'); ?>
        <?= $metric($money($funnel['successAmount']), 'Сумма успешных'); ?>
    </div>

    <ul class="sx-lead-report-bars sx-lead-funnel">
        <?php foreach ($funnel['steps'] as $step) : ?>
            <?php $width = $step['reached'] > 0 ? max(1, round($step['reached'] / $max * 100)) : 0; ?>
            <li class="sx-lead-report-bars__row" title="<?= Html::encode($step['title'].': '.$step['reached']); ?>">
                <span class="sx-lead-report-bars__label"><?= Html::encode($step['title']); ?></span>
                <span class="sx-lead-report-bars__track">
                    <?php if ($width) : ?>
                        <span class="sx-lead-report-bars__bar" style="width: <?= (int)$width; ?>%<?= $step['color'] ? '; background: '.Html::encode($step['color']) : ''; ?>"></span>
                    <?php endif; ?>
                </span>
                <span class="sx-lead-report-bars__value">
                    <?= $formatter->asInteger($step['reached']); ?>
                    <?php if ($step['fromPrevious'] !== null) : ?>
                        <span class="sx-lead-report-bars__hint"><?= $percent($step['fromPrevious']); ?></span>
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php BackendSurfaceWidget::end(); ?>

<div class="sx-lead-report__columns">
    <?php BackendSurfaceWidget::begin(['title' => 'Время на этапах', 'hint' => 'Сколько сделки находятся на этапе: среднее и медиана', 'raised' => true]); ?>
        <div class="sx-data-table-wrapper">
            <table class="sx-data-table">
                <thead><tr><th>Этап</th><th class="sx-lead-report__num">Переходов</th><th class="sx-lead-report__num">В среднем</th><th class="sx-lead-report__num">Медиана</th></tr></thead>
                <tbody>
                <?php foreach ($funnel['stageTimes'] as $row) : ?>
                    <tr>
                        <td><?= Html::encode($row['title']); ?></td>
                        <td class="sx-lead-report__num"><?= $formatter->asInteger($row['count']); ?></td>
                        <td class="sx-lead-report__num"><?= $duration($row['average']); ?></td>
                        <td class="sx-lead-report__num"><?= $duration($row['median']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin(['title' => 'Первые и повторные продажи', 'hint' => 'Успешные сделки, закрытые в периоде', 'raised' => true]); ?>
        <div class="sx-data-table-wrapper">
            <table class="sx-data-table">
                <thead><tr><th></th><th class="sx-lead-report__num">Сделок</th><th class="sx-lead-report__num">Сумма</th></tr></thead>
                <tbody>
                <tr><td>Первая продажа клиенту</td><td class="sx-lead-report__num"><?= (int)$sales['first']; ?></td><td class="sx-lead-report__num"><?= $money($sales['firstAmount']); ?></td></tr>
                <tr><td>Повторная продажа</td><td class="sx-lead-report__num"><?= (int)$sales['repeat']; ?></td><td class="sx-lead-report__num"><?= $money($sales['repeatAmount']); ?></td></tr>
                <?php if ($sales['unknown']) : ?>
                    <tr><td>Без клиента в CRM <span class="sx-lead-report-bars__hint">не определить</span></td><td class="sx-lead-report__num"><?= (int)$sales['unknown']; ?></td><td class="sx-lead-report__num"><?= $money($sales['unknownAmount'] ?? 0); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php BackendSurfaceWidget::end(); ?>
</div>

<?php if ($funnel['bySource'] || $funnel['byExecutor']) : ?>
    <div class="sx-lead-report__columns">
        <?php BackendSurfaceWidget::begin(['title' => 'Источники', 'hint' => 'Сделки, попавшие в воронку в периоде', 'raised' => true, 'bodyFlush' => true]); ?>
            <?= $groupTable($funnel['bySource'], 'Источник'); ?>
        <?php BackendSurfaceWidget::end(); ?>
        <?php BackendSurfaceWidget::begin(['title' => 'Ответственные', 'hint' => 'Сделки, попавшие в воронку в периоде', 'raised' => true, 'bodyFlush' => true]); ?>
            <?= $groupTable($funnel['byExecutor'], 'Сотрудник'); ?>
        <?php BackendSurfaceWidget::end(); ?>
    </div>
<?php endif; ?>
