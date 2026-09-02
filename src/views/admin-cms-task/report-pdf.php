<?php
/**
 * @var yii\web\View $this
 * @var array $report
 * @var array|null $theme
 */

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$columns = (array)ArrayHelper::getValue($report, 'params.columns', []);
$labels = (array)ArrayHelper::getValue($report, 'columns', []);
$taskView = (string)ArrayHelper::getValue($report, 'params.task_view', 'list');
$showTime = (bool)array_intersect(['fact_time', 'fact_hours'], $columns);
$params = (array)ArrayHelper::getValue($report, 'params', []);
$filterRows = [];

if (!empty($params['cms_company_id'])) {
    $model = CmsCompany::findOne((int)$params['cms_company_id']);
    if ($model) { $filterRows[] = ['Компания', (string)$model->asText]; }
}
if (!empty($params['cms_project_id'])) {
    $model = CmsProject::findOne((int)$params['cms_project_id']);
    if ($model) { $filterRows[] = ['Проект', (string)$model->asText]; }
}
if (!empty($params['cms_user_id'])) {
    $model = CmsUser::findOne((int)$params['cms_user_id']);
    if ($model) { $filterRows[] = ['Клиент', (string)$model->asText]; }
}
if (!empty($params['executor_id'])) {
    $model = CmsUser::findOne((int)$params['executor_id']);
    if ($model) { $filterRows[] = ['Исполнитель', (string)$model->asText]; }
}
if (!empty($params['status'])) {
    $statuses = CmsTask::statuses();
    $statusNames = [];
    foreach ((array)$params['status'] as $status) {
        $statusNames[] = ArrayHelper::getValue($statuses, $status, $status);
    }
    if ($statusNames) { $filterRows[] = ['Статус', implode(', ', $statusNames)]; }
}

$isBranded = (bool)$theme;
$background = $isBranded ? ArrayHelper::getValue($theme, 'background_color') : '#ffffff';
$surface = $isBranded ? ArrayHelper::getValue($theme, 'surface_color') : '#ffffff';
$text = $isBranded ? ArrayHelper::getValue($theme, 'text_color') : '#222222';
$muted = $isBranded ? ArrayHelper::getValue($theme, 'muted_color') : '#555555';
$border = $isBranded ? ArrayHelper::getValue($theme, 'border_color') : '#dddddd';
$accent = $isBranded ? ArrayHelper::getValue($theme, 'accent_color') : '#cccccc';
$accentAlt = $isBranded ? ArrayHelper::getValue($theme, 'accent_alt_color', $accent) : '#cccccc';
$success = $isBranded ? ArrayHelper::getValue($theme, 'success_color', $accent) : '#555555';
$warning = $isBranded ? ArrayHelper::getValue($theme, 'warning_color', $accent) : '#555555';
$showCover = $isBranded && ArrayHelper::getValue($theme, 'show_cover');
$logoSrc = $isBranded ? ArrayHelper::getValue($theme, 'logo_src') : null;
$companyName = $isBranded ? ArrayHelper::getValue($theme, 'company_name') : null;
$companyUrl = $isBranded ? ArrayHelper::getValue($theme, 'company_url') : null;
$logoBackdrop = $isBranded && ArrayHelper::getValue($theme, 'theme') == 'skeeks-light' ? '#11121a' : 'transparent';
$coverSubject = '';
foreach ($filterRows as $filterRow) {
    if (in_array($filterRow[0], ['Компания', 'Проект', 'Клиент'], true)) {
        $coverSubject = (string)$filterRow[1];
        break;
    }
}
$taskCount = (int)ArrayHelper::getValue($report, 'summary.tasks');
$hours = (float)ArrayHelper::getValue($report, 'summary.hours');
$period = (string)ArrayHelper::getValue($report, 'params.period');
$isTwoColumn = $isBranded && ArrayHelper::getValue($theme, 'page_orientation') == 'landscape';
$documentBg = $isBranded ? 'transparent' : $background;

/**
 * Ширина плашек обложки считается в миллиметрах: mPDF берёт проценты без
 * учёта padding, и блоки перестают попадать в ширину сетки.
 *
 * 178/265 мм — рабочая ширина A4 при полях 16 мм, 12 мм — горизонтальные
 * padding плашки, 4 мм — её горизонтальные margin.
 */
$contentWidth = $isTwoColumn ? 265 : 178;
/**
 * Плашки метрик разделены зазором в 6 мм — столько же, сколько между рядами.
 * Зазор не может быть margin: mPDF схлопывает его между плавающими блоками.
 * Поэтому плашка лежит в прозрачной колонке, которая шире её на зазор.
 */
$metricGap = 6;
$metricColumnWidth = round(($contentWidth - $metricGap) / 2, 1);
$metricWidth = $metricColumnWidth - 12;
$metricWideWidth = $contentWidth - 12;

/**
 * Текст результата в фирменную разметку: строки-перечисления становятся
 * списком с цветным маркером, остальное — обычными абзацами.
 *
 * @param string $value
 * @param string $bulletColor
 *
 * @return string
 */
$renderResultText = function ($value, $bulletColor) {
    $lines = preg_split('/\r\n|\r|\n/', (string)$value);
    $html = '';
    $paragraph = [];
    $bullets = [];

    $flushParagraph = function () use (&$paragraph, &$html) {
        if ($paragraph) {
            $html .= '<div class="task-text">'.implode('<br>', array_map(function ($line) {
                return Html::encode($line);
            }, $paragraph)).'</div>';
            $paragraph = [];
        }
    };
    $flushBullets = function () use (&$bullets, &$html, $bulletColor) {
        if ($bullets) {
            $html .= '<table class="task-bullets" cellspacing="0" cellpadding="0">';
            foreach ($bullets as $bullet) {
                $html .= '<tr>'
                    .'<td class="task-bullet" style="color:'.$bulletColor.';">&#9679;</td>'
                    .'<td class="task-bullet-text">'.Html::encode($bullet).'</td>'
                    .'</tr>';
            }
            $html .= '</table>';
            $bullets = [];
        }
    };

    foreach ($lines as $line) {
        $line = rtrim($line);
        $trimmed = ltrim($line);

        if ($trimmed === '') {
            $flushParagraph();
            $flushBullets();
            continue;
        }

        if (preg_match('/^[•\-\*–—]\s+(.+)$/u', $trimmed, $matches)) {
            $flushParagraph();
            $bullets[] = $matches[1];
            continue;
        }

        $flushBullets();
        $paragraph[] = $trimmed;
    }

    $flushParagraph();
    $flushBullets();

    return $html;
};
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /**
         * Фон документа приходит подложкой отдельным PDF-шаблоном: mPDF
         * печатает фоновые градиенты после всех сплошных заливок и иначе
         * перекрывает ими карточки.
         */
        body {
            margin: 0;
            background: <?php echo Html::encode($documentBg); ?>;
            background-image: none;
            color: <?php echo Html::encode($text); ?>;
            font-family: dejavusans, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
        }
        h1 { color: <?php echo Html::encode($text); ?>; font-size: <?php echo $isBranded ? '21pt' : '18pt'; ?>; line-height: 1.15; margin: 0 0 6px; }
        .brand-header { color: <?php echo Html::encode($muted); ?>; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        .brand-header-logo { background: <?php echo Html::encode($logoBackdrop); ?>; border-radius: 3px; max-height: 22px; max-width: 96px; padding: <?php echo $logoBackdrop == 'transparent' ? '0' : '3px 5px'; ?>; }
        .document-intro { margin: 0 0 14px; }
        .document-kicker { color: <?php echo Html::encode($accent); ?>; font-size: 8pt; font-weight: bold; letter-spacing: 1.4px; margin-bottom: 6px; text-transform: uppercase; }
        .document-subject { color: <?php echo Html::encode($muted); ?>; font-size: 10pt; }
        .card {
            background-color: <?php echo Html::encode($surface); ?> !important;
            background-image: none;
            border-radius: 10px;
            margin: 0 0 12px;
            padding: 13px 16px;
            page-break-inside: avoid;
        }
        .card-label { font-size: 7.5pt; font-weight: bold; letter-spacing: 1.2px; margin-bottom: 7px; text-transform: uppercase; }
        .summary-value { color: <?php echo Html::encode($text); ?>; }
        .summary-label { color: <?php echo Html::encode($muted); ?>; }
        table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { border: 1px solid <?php echo Html::encode($border); ?>; padding: 6px; vertical-align: top; }
        .data-table th { background: <?php echo Html::encode($surface); ?>; color: <?php echo Html::encode($text); ?>; font-weight: bold; }
        .result { white-space: pre-line; }
        .task-list { margin: 0 <?php echo $isTwoColumn ? '-6px' : '0'; ?>; }
        .task-item {
            background-color: <?php echo Html::encode($surface); ?> !important;
            background-image: none;
            border-radius: 10px;
            float: <?php echo $isTwoColumn ? 'left' : 'none'; ?>;
            margin: <?php echo $isTwoColumn ? '0 6px 12px' : '0 0 12px'; ?>;
            page-break-inside: avoid;
            padding: 14px 16px;
            <?php if ($isTwoColumn) : ?>width: 46%;<?php endif; ?>
        }
        /**
         * Задача, которая не помещается на страницу, собирается из отдельных
         * неразрывных секций: mPDF заливает фон блока только на той странице,
         * где блок начался.
         */
        .task-section {
            background-color: <?php echo Html::encode($surface); ?> !important;
            background-image: none;
            margin: <?php echo $isTwoColumn ? '0 6px' : '0'; ?>;
            padding: 2mm 16px;
            page-break-inside: avoid;
            <?php if ($isTwoColumn) : ?>width: 46%;<?php endif; ?>
        }
        .task-section-first { border-radius: 10px 10px 0 0; padding-top: 14px; }
        .task-section-last { border-radius: 0 0 10px 10px; margin-bottom: 12px; padding-bottom: 14px; }
        .task-title { color: <?php echo Html::encode($text); ?>; font-size: 11.5pt; font-weight: bold; line-height: 1.3; margin: 0 0 6px; }
        .task-meta { color: <?php echo Html::encode($muted); ?>; font-size: 8pt; line-height: 1.5; margin: 0 0 4px; }
        .task-meta span { display: inline-block; margin: 0 14px 2px 0; }
        .task-result-label { color: <?php echo Html::encode($success); ?>; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.2px; margin: 10px 0 6px; text-transform: uppercase; }
        .task-result-item { page-break-inside: avoid; }
        .task-text { color: <?php echo Html::encode($text); ?>; font-size: 9pt; line-height: 1.5; margin: 0 0 4px; }
        .task-bullets { margin: 0 0 4px; width: 100%; }
        .task-bullets td { border: 0; }
        .task-bullet { font-size: 5.5pt; padding: 4px 5px 0 0; vertical-align: top; width: 5mm; }
        .task-bullet-text { color: <?php echo Html::encode($text); ?>; font-size: 9pt; line-height: 1.5; padding: 0 0 3px; vertical-align: top; }
        .task-attachments { margin-top: 10px; page-break-inside: avoid; }
        .task-attachments-title { color: <?php echo Html::encode($muted); ?>; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.2px; margin-bottom: 6px; text-transform: uppercase; }
        .task-attachments-table { border-collapse: separate; border-spacing: 0 5px; margin: 0; width: 100%; }
        .task-attachments-table td { background-color: <?php echo Html::encode($background); ?> !important; border: 0; border-radius: 6px; padding: 7px 10px; vertical-align: middle; }
        .task-attachment-image { max-height: 42mm; max-width: 100%; }
        .task-attachment-name { color: <?php echo Html::encode($muted); ?>; font-size: 8pt; line-height: 1.35; padding-top: 4px; word-break: break-word; }
        .task-attachment-file { color: <?php echo Html::encode($text); ?>; font-size: 8.5pt; line-height: 1.4; text-decoration: none; word-break: break-word; }
        .cover { padding: 4mm 0 0; }
        .cover-brand { margin-bottom: 14mm; }
        .cover-logo { background: <?php echo Html::encode($logoBackdrop); ?>; border-radius: 2mm; height: auto; max-height: 24mm; max-width: 82mm; padding: <?php echo $logoBackdrop == 'transparent' ? '0' : '2mm 3mm'; ?>; width: 54mm; }
        .cover-kicker { color: <?php echo Html::encode($accent); ?>; font-size: 8.5pt; font-weight: bold; letter-spacing: 1.4px; margin-bottom: 4mm; text-transform: uppercase; }
        .cover-title { color: <?php echo Html::encode($text); ?>; font-size: 29pt; font-weight: bold; line-height: 1.1; margin: 0 0 4mm; }
        .cover-subject { color: <?php echo Html::encode($accent); ?>; font-size: 17pt; font-weight: bold; margin-bottom: 9mm; }
        /* Скругления mPDF рисует только у блоков, но не у ячеек таблицы. */
        .cover-status {
            background-color: <?php echo Html::encode($surface); ?> !important;
            background-image: none;
            border-radius: 7mm;
            color: <?php echo Html::encode($text); ?>;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0 0 12mm;
            padding: 3.5mm 7mm;
            width: 52mm;
        }
        .cover-status-dot { color: <?php echo Html::encode($success); ?>; }
        .cover-metrics { margin: 0 0 10mm; }
        /* Прозрачная колонка: её лишняя ширина и образует зазор между плашками. */
        .cover-metric-col { float: left; }
        .cover-metric {
            background-color: <?php echo Html::encode($surface); ?> !important;
            background-image: none;
            border-radius: 4mm;
            margin: 0;
            padding: 6mm;
        }
        /**
         * mPDF не выравнивает плавающие блоки по высоте, а явная height ломает
         * обтекание. Блоки уравнены одинаковой высотой строки со значением.
         */
        .cover-metric-wide { float: none; margin-top: 6mm; }
        .cover-metric-value { font-size: 19pt; font-weight: bold; line-height: 1.2; margin-bottom: 2mm; }
        .cover-metric-value-small { font-size: 12.5pt; line-height: 1.82; }
        .cover-metric-label { color: <?php echo Html::encode($muted); ?>; font-size: 8pt; line-height: 1.35; }
        .cover-summary {
            background-color: <?php echo Html::encode($surface); ?> !important;
            background-image: none;
            border-radius: 4mm;
            margin: 0 0 10mm;
            padding: 6mm;
        }
        .cover-summary-label { color: <?php echo Html::encode($success); ?>; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.2px; margin-bottom: 3mm; }
        .cover-summary-title { color: <?php echo Html::encode($text); ?>; font-size: 14pt; font-weight: bold; line-height: 1.25; margin-bottom: 3mm; }
        .cover-summary-text { color: <?php echo Html::encode($muted); ?>; font-size: 8.5pt; line-height: 1.5; }
        .cover-company { color: <?php echo Html::encode($muted); ?>; font-size: 8.5pt; line-height: 1.6; }
        .page-break { page-break-after: always; }
        .clear { clear: both; }
    </style>
</head>
<body>
<?php if ($isBranded) : ?>
<htmlpageheader name="brand">
    <table width="100%" cellspacing="0" cellpadding="0" style="border: 0;">
        <tr>
            <td width="50%" style="border: 0; padding: 0;">
                <?php if ($logoSrc) : ?><img class="brand-header-logo" src="<?php echo Html::encode($logoSrc); ?>" alt=""><?php endif; ?>
            </td>
            <td width="50%" align="right" class="brand-header" style="border: 0; padding: 0 30mm 0 0;">
                <?php echo Html::encode($coverSubject !== '' ? $coverSubject : 'Отчёт по задачам'); ?>
            </td>
        </tr>
    </table>
</htmlpageheader>
<?php endif; ?>

<?php if ($showCover) : ?>
    <section class="cover">
        <div class="cover-brand"><?php if ($logoSrc) : ?><img class="cover-logo" src="<?php echo Html::encode($logoSrc); ?>" alt=""><?php endif; ?></div>
        <div class="cover-kicker">Отчёт по задачам</div>
        <div class="cover-title">Работы и результаты</div>
        <?php if ($coverSubject !== '') : ?><div class="cover-subject"><?php echo Html::encode($coverSubject); ?></div><?php endif; ?>
        <div class="cover-status" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:3.5mm 7mm;"><span class="cover-status-dot">&#9679;</span>&nbsp; ОТЧЁТ СФОРМИРОВАН</div>
        <div class="cover-metrics">
            <div class="cover-metric-col" style="width:<?php echo $metricColumnWidth + $metricGap; ?>mm;">
                <div class="cover-metric" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:6mm;width:<?php echo $metricWidth; ?>mm;">
                    <div class="cover-metric-value" style="color: <?php echo Html::encode($accent); ?>;"><?php echo $taskCount; ?></div>
                    <div class="cover-metric-label">задач в отчёте</div>
                </div>
            </div>
            <div class="cover-metric-col" style="width:<?php echo $metricColumnWidth; ?>mm;">
                <?php if ($showTime) : ?>
                <div class="cover-metric" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:6mm;width:<?php echo $metricWidth; ?>mm;">
                    <div class="cover-metric-value" style="color: <?php echo Html::encode($accentAlt); ?>;"><?php echo Html::encode(\Yii::$app->formatter->asDecimal($hours, 1)); ?></div>
                    <div class="cover-metric-label">отработано часов</div>
                </div>
                <?php else : ?>
                <div class="cover-metric" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:6mm;width:<?php echo $metricWidth; ?>mm;">
                    <div class="cover-metric-value cover-metric-value-small" style="color: <?php echo Html::encode($warning); ?>;"><?php echo Html::encode($period); ?></div>
                    <div class="cover-metric-label">отчётный период</div>
                </div>
                <?php endif; ?>
            </div>
            <div class="clear"></div>
            <?php if ($showTime) : ?>
            <div class="cover-metric cover-metric-wide" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:6mm;width:<?php echo $metricWideWidth; ?>mm;">
                <div class="cover-metric-value cover-metric-value-small" style="color: <?php echo Html::encode($warning); ?>;"><?php echo Html::encode($period); ?></div>
                <div class="cover-metric-label">отчётный период</div>
            </div>
            <div class="clear"></div>
            <?php endif; ?>
        </div>
        <div class="cover-summary" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:6mm;width:<?php echo $metricWideWidth; ?>mm;">
            <div class="cover-summary-label">ИТОГ</div>
            <div class="cover-summary-title">Работы по периоду выполнены и зафиксированы</div>
            <div class="cover-summary-text">В отчёт вошли задачи за выбранный период с описанием результата по каждой работе<?php if ($coverSubject !== '') : ?> для «<?php echo Html::encode($coverSubject); ?>»<?php endif; ?>.</div>
        </div>
        <div class="cover-company">
            <?php echo Html::encode('Дата отчёта: '.\Yii::$app->formatter->asDate(time(), 'long')); ?><br>
            <?php if ($companyName || $companyUrl) : ?>
                Подготовлено: <?php echo Html::encode($companyName); ?><?php if ($companyUrl) : ?> · <?php echo Html::encode($companyUrl); ?><?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    <pagebreak />
    <!-- skeeks-mpdf-chunk -->
<?php endif; ?>

<?php if ($isBranded) : ?><sethtmlpageheader name="brand" value="on" show-this-page="1" /><?php endif; ?>

<div class="document-intro">
    <?php if ($isBranded) : ?><div class="document-kicker">Отчёт по задачам</div><?php endif; ?>
    <h1><?php echo Html::encode(ArrayHelper::getValue($report, 'title', 'Отчёт по задачам')); ?></h1>
</div>

<div class="card" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:13px 16px;">
    <div class="card-label" style="color: <?php echo Html::encode($accent); ?>;">Параметры отчёта</div>
    <span class="summary-label">Период:</span> <span class="summary-value"><?php echo Html::encode($period); ?></span><br>
    <?php foreach ($filterRows as $filterRow) : ?>
        <span class="summary-label"><?php echo Html::encode($filterRow[0]); ?>:</span> <span class="summary-value"><?php echo Html::encode($filterRow[1]); ?></span><br>
    <?php endforeach; ?>
    <span class="summary-label">Задач:</span> <span class="summary-value"><?php echo $taskCount; ?></span><?php if ($showTime) : ?><br>
    <span class="summary-label">Отработанное время:</span> <span class="summary-value"><?php echo Html::encode(CmsScheduleHelper::durationAsText((int)ArrayHelper::getValue($report, 'summary.duration'))); ?></span><br>
    <span class="summary-label">Отработано часов:</span> <span class="summary-value"><?php echo Html::encode(\Yii::$app->formatter->asDecimal($hours, 1)); ?></span><?php endif; ?>
</div>
<!-- skeeks-mpdf-chunk --><?php // Безопасная граница потоковой записи mPDF. ?>

<?php if ($taskView == 'list') : ?>
    <div class="task-list">
        <?php foreach ((array)ArrayHelper::getValue($report, 'rows') as $row) : ?>
            <?php
            $resultItems = (array)ArrayHelper::getValue($row, 'result_items', []);
            if (!$resultItems && (ArrayHelper::getValue($row, 'result') !== '' || ArrayHelper::getValue($row, 'result_files'))) {
                $resultItems[] = [
                    'text'  => (string)ArrayHelper::getValue($row, 'result', ''),
                    'files' => (array)ArrayHelper::getValue($row, 'result_files', []),
                ];
            }
            $hasSplittableResult = false;
            foreach ($resultItems as $resultItem) {
                $resultText = (string)ArrayHelper::getValue($resultItem, 'text', '');
                if (mb_strlen($resultText) >= 2000 || substr_count($resultText, "\n") >= 18) {
                    $hasSplittableResult = true;
                    break;
                }
            }

            /**
             * Содержимое карточки собирается по частям: короткая задача
             * выводится одним блоком, длинная — цепочкой неразрывных секций.
             */
            $sections = [];

            ob_start();
            ?>
                <div class="task-title"><?php echo Html::encode(ArrayHelper::getValue($row, 'name')); ?></div>
                <div class="task-meta">
                    <?php foreach ($columns as $column) : ?>
                        <?php if (in_array($column, ['name', 'result'])) { continue; } ?>
                        <?php $value = ArrayHelper::getValue($row, $column); ?>
                        <?php if ($value === null || $value === '') { continue; } ?>
                        <span><b><?php echo Html::encode(ArrayHelper::getValue($labels, $column, $column)); ?>:</b> <?php echo Html::encode($value); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php
            $sections[] = ob_get_clean();

            if (in_array('result', $columns) && $resultItems) {
                $resultLabelPrinted = false;
                foreach ($resultItems as $resultItem) {
                    $resultFiles = (array)ArrayHelper::getValue($resultItem, 'files', []);
                    $resultText = (string)ArrayHelper::getValue($resultItem, 'text', '');
                    $resultChunks = $hasSplittableResult
                        ? array_map(function ($lines) {
                            return implode("\n", $lines);
                        }, array_chunk(preg_split('/\r\n|\r|\n/', $resultText), 6))
                        : [$resultText];

                    if ($resultText !== '') {
                        foreach ($resultChunks as $resultChunk) {
                            ob_start();
                            ?>
                    <div class="task-result-item">
                        <?php if (!$resultLabelPrinted) : ?>
                        <div class="task-result-label">Результат</div>
                        <?php endif; ?>
                        <?php echo $renderResultText($resultChunk, $accent); ?>
                    </div>
                            <?php
                            $sections[] = ob_get_clean();
                            $resultLabelPrinted = true;
                        }
                    }

                    if ($resultFiles) {
                        ob_start();
                        ?>
                    <div class="task-attachments">
                        <div class="task-attachments-title">Вложения к результату</div>
                        <table class="task-attachments-table" cellspacing="0" cellpadding="0">
                            <?php foreach ($resultFiles as $file) : ?>
                                <tr><td bgcolor="<?php echo Html::encode($background); ?>">
                                    <?php if (ArrayHelper::getValue($file, 'isImage') && ArrayHelper::getValue($file, 'src')) : ?>
                                        <?php if (ArrayHelper::getValue($file, 'url')) : ?><a href="<?php echo Html::encode(ArrayHelper::getValue($file, 'url')); ?>"><?php endif; ?>
                                        <img class="task-attachment-image" src="<?php echo Html::encode(ArrayHelper::getValue($file, 'src')); ?>" alt="">
                                        <?php if (ArrayHelper::getValue($file, 'url')) : ?></a><?php endif; ?>
                                        <div class="task-attachment-name"><?php echo Html::encode(ArrayHelper::getValue($file, 'name', 'Изображение')); ?></div>
                                    <?php endif; ?>
                                    <?php if (!ArrayHelper::getValue($file, 'isImage')) : ?>
                                        <?php if (ArrayHelper::getValue($file, 'url')) : ?>
                                            <a class="task-attachment-file" href="<?php echo Html::encode(ArrayHelper::getValue($file, 'url')); ?>">Файл: <?php echo Html::encode(ArrayHelper::getValue($file, 'name', 'Скачать вложение')); ?></a>
                                        <?php else : ?>
                                            <span class="task-attachment-file">Файл: <?php echo Html::encode(ArrayHelper::getValue($file, 'name', 'Вложение')); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                        <?php
                        $sections[] = ob_get_clean();
                    }
                }
            }

            $lastSection = count($sections) - 1;
            ?>
            <?php if ($hasSplittableResult) : ?>
                <?php foreach ($sections as $sectionIndex => $sectionHtml) : ?>
                    <div class="task-section<?php echo $sectionIndex == 0 ? ' task-section-first' : ''; ?><?php echo $sectionIndex == $lastSection ? ' task-section-last' : ''; ?>" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;"><?php echo $sectionHtml; ?></div>
                    <!-- skeeks-mpdf-chunk -->
                <?php endforeach; ?>
            <?php else : ?>
                <div class="task-item" style="background:<?php echo Html::encode($surface); ?>;background-color:<?php echo Html::encode($surface); ?>;padding:14px 16px;"><?php echo implode('', $sections); ?></div>
                <!-- skeeks-mpdf-chunk -->
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="clear"></div>
        <?php if (!$report['rows']) : ?><div class="card">По выбранным условиям данных нет.</div><?php endif; ?>
    </div>
<?php else : ?>
    <table class="data-table">
        <thead><tr>
            <?php foreach ($columns as $column) : ?><th><?php echo Html::encode(ArrayHelper::getValue($labels, $column, $column)); ?></th><?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ((array)ArrayHelper::getValue($report, 'rows') as $row) : ?>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <td class="<?php echo $column == 'result' ? 'result' : ''; ?>" bgcolor="<?php echo Html::encode($surface); ?>"><?php echo Html::encode(ArrayHelper::getValue($row, $column)); ?></td>
                <?php endforeach; ?>
            </tr>
            <!-- skeeks-mpdf-chunk -->
        <?php endforeach; ?>
        <?php if (!$report['rows']) : ?><tr><td colspan="<?php echo max(1, count($columns)); ?>">По выбранным условиям данных нет.</td></tr><?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
