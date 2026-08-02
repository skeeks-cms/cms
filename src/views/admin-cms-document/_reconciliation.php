<?php
/* @var $model \skeeks\cms\shop\models\ShopDocument */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use skeeks\cms\money\Currency;

$data = (array)ArrayHelper::getValue((array)$model->document_data, 'reconciliation_act', []);
$operations = (array)ArrayHelper::getValue($data, 'operations', []);
$currencyCode = (string)ArrayHelper::getValue($data, 'currency_code', $model->currency_code ?: 'RUB');
$currencyLabel = $currencyCode === 'RUB'
    ? 'руб.'
    : ((string)Currency::getInstance($currencyCode)->symbol ?: $currencyCode);
$money = function ($amount) use ($currencyLabel) {
    return number_format((float)$amount, 2, ',', ' ').' '.$currencyLabel;
};
$date = function ($value) {
    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d.m.Y', $timestamp) : '';
};

$this->registerCss(<<<CSS
.sx-reconciliation-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}
.sx-reconciliation-summary-card {
    padding: 15px;
    border: 1px solid #e3e7eb;
    border-radius: 8px;
    background: #f8fafb;
}
.sx-reconciliation-summary-label {
    margin-bottom: 5px;
    color: #7b858f;
    font-size: 12px;
}
.sx-reconciliation-summary-value {
    font-size: 20px;
    font-weight: 600;
}
.sx-reconciliation-admin-table {
    width: 100%;
    border-collapse: collapse;
}
.sx-reconciliation-admin-table th,
.sx-reconciliation-admin-table td {
    padding: 10px;
    border-bottom: 1px solid #e3e7eb;
    vertical-align: top;
}
.sx-reconciliation-admin-table th {
    color: #6d767f;
    background: #f8fafb;
    text-align: left;
}
.sx-reconciliation-admin-table .sx-money {
    text-align: right;
    white-space: nowrap;
}
.sx-reconciliation-admin-table .sx-total td {
    font-weight: 600;
    background: #f8fafb;
}
@media (max-width: 760px) {
    .sx-reconciliation-summary {
        grid-template-columns: 1fr;
    }
}
CSS
);
?>

<div class="sx-document-section">
    <h3 class="sx-document-section-title">
        Взаиморасчеты с <?= Html::encode($date(ArrayHelper::getValue($data, 'period_start'))); ?>
        по <?= Html::encode($date(ArrayHelper::getValue($data, 'period_end'))); ?>
    </h3>

    <div class="sx-reconciliation-summary">
        <div class="sx-reconciliation-summary-card">
            <div class="sx-reconciliation-summary-label">Сальдо на начало</div>
            <div class="sx-reconciliation-summary-value"><?= Html::encode($money(ArrayHelper::getValue($data, 'opening_balance', 0))); ?></div>
        </div>
        <div class="sx-reconciliation-summary-card">
            <div class="sx-reconciliation-summary-label">Обороты: дебет / кредит</div>
            <div class="sx-reconciliation-summary-value">
                <?= Html::encode($money(ArrayHelper::getValue($data, 'turnover_debit', 0))); ?> /
                <?= Html::encode($money(ArrayHelper::getValue($data, 'turnover_credit', 0))); ?>
            </div>
        </div>
        <div class="sx-reconciliation-summary-card">
            <div class="sx-reconciliation-summary-label">Сальдо на конец</div>
            <div class="sx-reconciliation-summary-value"><?= Html::encode($money(ArrayHelper::getValue($data, 'closing_balance', 0))); ?></div>
        </div>
    </div>

    <div class="sx-document-items-wrap">
        <table class="sx-reconciliation-admin-table">
            <thead>
                <tr>
                    <th style="width: 110px;">Дата</th>
                    <th>Документ или операция</th>
                    <th class="sx-money" style="width: 150px;">Дебет</th>
                    <th class="sx-money" style="width: 150px;">Кредит</th>
                </tr>
            </thead>
            <tbody>
                <tr class="sx-total">
                    <td></td>
                    <td>Сальдо на начало периода</td>
                    <td class="sx-money"><?= Html::encode($money(ArrayHelper::getValue($data, 'opening_debit', 0))); ?></td>
                    <td class="sx-money"><?= Html::encode($money(ArrayHelper::getValue($data, 'opening_credit', 0))); ?></td>
                </tr>
                <?php foreach ($operations as $operation) : ?>
                    <tr>
                        <td><?= Html::encode($date(ArrayHelper::getValue($operation, 'date'))); ?></td>
                        <td><?= Html::encode(ArrayHelper::getValue($operation, 'description')); ?></td>
                        <td class="sx-money"><?= (float)ArrayHelper::getValue($operation, 'debit', 0) ? Html::encode($money(ArrayHelper::getValue($operation, 'debit'))) : ''; ?></td>
                        <td class="sx-money"><?= (float)ArrayHelper::getValue($operation, 'credit', 0) ? Html::encode($money(ArrayHelper::getValue($operation, 'credit'))) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="sx-total">
                    <td></td>
                    <td>Обороты за период</td>
                    <td class="sx-money"><?= Html::encode($money(ArrayHelper::getValue($data, 'turnover_debit', 0))); ?></td>
                    <td class="sx-money"><?= Html::encode($money(ArrayHelper::getValue($data, 'turnover_credit', 0))); ?></td>
                </tr>
                <tr class="sx-total">
                    <td></td>
                    <td>Сальдо на конец периода</td>
                    <td class="sx-money"><?= Html::encode($money(ArrayHelper::getValue($data, 'closing_debit', 0))); ?></td>
                    <td class="sx-money"><?= Html::encode($money(ArrayHelper::getValue($data, 'closing_credit', 0))); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
