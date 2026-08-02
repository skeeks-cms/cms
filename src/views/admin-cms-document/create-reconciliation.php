<?php
/* @var $this yii\web\View */
/* @var $company \skeeks\cms\models\CmsCompany */
/* @var $formModel \skeeks\cms\base\DynamicModel */
/* @var $ourItems array */
/* @var $counterpartyItems array */
/* @var $currencyItems array */
/* @var $previewData array|null */

use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\cms\money\Currency;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->registerCss(<<<CSS
.sx-reconciliation-create {
    max-width: 920px;
    margin: 0 auto;
}
.sx-reconciliation-create-card {
    padding: 26px;
    border: 1px solid #e3e7eb;
    border-radius: 12px;
    background: #fff;
}
.sx-reconciliation-create-title {
    margin: 0 0 6px;
    font-size: 24px;
}
.sx-reconciliation-create-subtitle {
    margin-bottom: 24px;
    color: #77808a;
}
.sx-reconciliation-create-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 4px 18px;
}
.sx-reconciliation-create-grid .field-reconciliationact-period {
    grid-column: 1 / -1;
}
.sx-reconciliation-create-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 16px;
}
.sx-reconciliation-note {
    display: flex;
    gap: 12px;
    margin-top: 4px;
    padding: 14px 16px;
    border: 1px solid #b8e0ea;
    border-radius: 10px;
    background: #edf8fb;
    color: #214b55;
    line-height: 1.45;
}
.sx-reconciliation-note > i {
    flex: 0 0 auto;
    margin-top: 3px;
    color: #168aa3;
    font-size: 18px;
}
.sx-reconciliation-note strong {
    display: block;
    margin-bottom: 2px;
    color: #183e47;
}
.sx-reconciliation-submit-status {
    margin-top: 12px;
    padding: 11px 14px;
    border-radius: 8px;
    background: #eef5ff;
    color: #234e7d;
    font-weight: 600;
}
.sx-reconciliation-submit-status[hidden] {
    display: none !important;
}
.sx-reconciliation-create-actions .btn.sx-is-submitting {
    cursor: wait;
    pointer-events: none;
}
.sx-reconciliation-preview {
    margin-top: 22px;
}
.sx-reconciliation-preview table {
    width: 100%;
    border-collapse: collapse;
}
.sx-reconciliation-preview th,
.sx-reconciliation-preview td {
    padding: 9px;
    border-bottom: 1px solid #e3e7eb;
    vertical-align: top;
}
.sx-reconciliation-preview th {
    background: #f8fafb;
    text-align: left;
}
.sx-reconciliation-preview .sx-money {
    text-align: right;
    white-space: nowrap;
}
@media (max-width: 700px) {
    .sx-reconciliation-create-grid {
        grid-template-columns: 1fr;
    }
    .sx-reconciliation-create-grid .field-reconciliationact-period {
        grid-column: auto;
    }
}
CSS
);
?>

<div class="sx-reconciliation-create">
    <div class="sx-reconciliation-create-card">
        <h1 class="sx-reconciliation-create-title">Новый акт сверки</h1>
        <div class="sx-reconciliation-create-subtitle">
            Компания: <strong><?= Html::encode($company->asText); ?></strong>
        </div>

        <?php if (!$ourItems) : ?>
            <div class="alert alert-danger">Сначала добавьте хотя бы одно наше юридическое лицо в разделе «Реквизиты».</div>
        <?php endif; ?>
        <?php if (!$counterpartyItems) : ?>
            <div class="alert alert-danger">У компании нет привязанного юридического лица клиента.</div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin(['id' => 'sx-reconciliation-form']); ?>
        <div class="sx-reconciliation-create-grid">
            <?= $form->field($formModel, 'seller_contractor_id')->dropDownList($ourItems, [
                'prompt' => 'Выберите наше юридическое лицо',
            ])->label('Наша организация'); ?>

            <?= $form->field($formModel, 'buyer_contractor_id')->dropDownList($counterpartyItems, [
                'prompt' => 'Выберите юридическое лицо клиента',
            ])->label('Контрагент'); ?>

            <?= $form->field($formModel, 'period')->widget(DaterangeInputWidget::class, [
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => 'Период сверки',
                ],
            ])->label('Период'); ?>

            <?= $form->field($formModel, 'currency_code')->dropDownList($currencyItems)->label('Валюта'); ?>
        </div>

        <div class="sx-reconciliation-note" role="note">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <div>
                <strong>Что войдет в акт сверки</strong>
                АКТ, УПД, накладные и платежи между выбранными юридическими лицами. Счета используются только как основание и не создают отдельный оборот.
            </div>
        </div>

        <?= Html::input('hidden', null, 'preview', [
            'id' => 'sx-reconciliation-submit-action',
        ]); ?>
        <div id="sx-reconciliation-submit-status" class="sx-reconciliation-submit-status" role="status" aria-live="polite" hidden></div>
        <div class="sx-reconciliation-create-actions">
            <?= Html::submitButton('<i class="fa fa-search"></i> Предварительный просмотр', [
                'class' => 'btn btn-default js-reconciliation-submit',
                'name' => 'submit',
                'value' => 'preview',
                'data-action' => 'preview',
                'disabled' => !$ourItems || !$counterpartyItems,
            ]); ?>
            <?= Html::submitButton('<i class="fa fa-file-signature"></i> Сформировать акт', [
                'class' => 'btn btn-primary js-reconciliation-submit',
                'name' => 'submit',
                'value' => 'create',
                'data-action' => 'create',
                'disabled' => !$ourItems || !$counterpartyItems,
            ]); ?>
        </div>
        <?php ActiveForm::end(); ?>

        <?php if (is_array($previewData)) : ?>
            <?php
            $previewCurrency = (string)ArrayHelper::getValue($previewData, 'currency_code', 'RUB');
            $previewCurrencyLabel = $previewCurrency === 'RUB'
                ? 'руб.'
                : ((string)Currency::getInstance($previewCurrency)->symbol ?: $previewCurrency);
            $previewMoney = function ($amount) use ($previewCurrencyLabel) {
                return number_format((float)$amount, 2, ',', ' ').' '.$previewCurrencyLabel;
            };
            ?>
            <div class="sx-reconciliation-preview">
                <h3>Предварительный расчет</h3>
                <div class="alert alert-default">
                    Сальдо на начало: <strong><?= Html::encode($previewMoney(ArrayHelper::getValue($previewData, 'opening_balance', 0))); ?></strong>,
                    обороты: <strong><?= Html::encode($previewMoney(ArrayHelper::getValue($previewData, 'turnover_debit', 0))); ?></strong> /
                    <strong><?= Html::encode($previewMoney(ArrayHelper::getValue($previewData, 'turnover_credit', 0))); ?></strong>,
                    сальдо на конец: <strong><?= Html::encode($previewMoney(ArrayHelper::getValue($previewData, 'closing_balance', 0))); ?></strong>.
                </div>
                <table>
                    <thead>
                        <tr><th>Дата</th><th>Операция</th><th class="sx-money">Дебет</th><th class="sx-money">Кредит</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ((array)ArrayHelper::getValue($previewData, 'operations', []) as $operation) : ?>
                            <tr>
                                <td><?= Html::encode(date('d.m.Y', strtotime((string)ArrayHelper::getValue($operation, 'date')))); ?></td>
                                <td><?= Html::encode(ArrayHelper::getValue($operation, 'description')); ?></td>
                                <td class="sx-money"><?= (float)ArrayHelper::getValue($operation, 'debit', 0) ? Html::encode($previewMoney(ArrayHelper::getValue($operation, 'debit'))) : ''; ?></td>
                                <td class="sx-money"><?= (float)ArrayHelper::getValue($operation, 'credit', 0) ? Html::encode($previewMoney(ArrayHelper::getValue($operation, 'credit'))) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$this->registerJs(<<<JS
(function () {
    var \$form = $('#sx-reconciliation-form');
    if (!\$form.length) {
        return;
    }

    var \$buttons = \$form.find('.js-reconciliation-submit');
    var \$action = $('#sx-reconciliation-submit-action');
    var \$status = $('#sx-reconciliation-submit-status');
    var isSubmitting = false;

    \$buttons.each(function () {
        $(this).data('initialDisabled', this.disabled);
    });

    // The original button names keep the form usable without JavaScript.
    // With JavaScript, a hidden field reliably preserves the chosen action
    // after the visible buttons become disabled.
    \$action.attr('name', 'submit');
    \$buttons.removeAttr('name');

    var resetSubmittingState = function () {
        isSubmitting = false;
        \$form.removeData('sxReconciliationClickLocked');
        \$buttons.each(function () {
            var \$button = $(this);
            var originalHtml = \$button.data('originalHtml');
            if (originalHtml) {
                \$button.html(originalHtml);
            }
        }).removeClass('sx-is-submitting disabled').each(function () {
            var \$button = $(this);
            \$button.prop('disabled', Boolean(\$button.data('initialDisabled')));
            if (!\$button.prop('disabled')) {
                \$button.removeAttr('aria-disabled');
            }
        });
        \$status.prop('hidden', true).text('');
    };

    \$buttons.on('click', function () {
        if (isSubmitting || \$form.data('sxReconciliationClickLocked')) {
            return false;
        }

        var action = String($(this).data('action') || 'preview');
        \$action.val(action);
        \$form.data('sxReconciliationClickLocked', true);
        \$status.text('Проверяем данные…').prop('hidden', false);
    });

    \$form.on('afterValidate', function (event, messages, errorAttributes) {
        if (errorAttributes && errorAttributes.length) {
            resetSubmittingState();
        }
    });

    \$form.on('beforeSubmit', function () {
        if (isSubmitting) {
            return false;
        }

        isSubmitting = true;
        var action = \$action.val() || 'preview';
        var isCreate = action === 'create';
        var \$activeButton = \$buttons.filter('[data-action="' + action + '"]');

        \$buttons.each(function () {
            var \$button = $(this);
            if (!\$button.data('originalHtml')) {
                \$button.data('originalHtml', \$button.html());
            }
        }).addClass('sx-is-submitting disabled').attr('aria-disabled', 'true');

        \$activeButton.html(
            '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> ' +
            (isCreate ? 'Формируем акт…' : 'Собираем операции…')
        );
        \$status.text(
            isCreate
                ? 'Формируем акт сверки. Не закрывайте страницу…'
                : 'Собираем документы и платежи за выбранный период…'
        ).prop('hidden', false);

        // Submit explicitly after the loading state has had time to render.
        // The form lives inside a sidebox iframe where delegated Yii handlers
        // can otherwise consume the native submit without navigation.
        window.setTimeout(function () {
            var formElement = \$form.get(0);
            if (formElement) {
                window.HTMLFormElement.prototype.submit.call(formElement);
            }
        }, 50);

        return false;
    });

    $(window).on('pageshow.sxReconciliation', function (event) {
        if (event.originalEvent && event.originalEvent.persisted) {
            resetSubmittingState();
        }
    });
})();
JS
);
?>
