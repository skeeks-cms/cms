<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\shop\models\ShopBill */

use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\rbac\CmsManager;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$publicUrl = $model->getUrl(true);
$pdfUrl = Url::to(['/shop/shop-bill/pdf', 'code' => $model->code], true);
$pdfNoSignatureUrl = Url::to(['/shop/shop-bill/pdf', 'code' => $model->code, 'noSignature' => '1'], true);
$closeUrl = Url::to(['close', $controller->requestPkParamName => $model->id]);
$publicUrlJson = Json::htmlEncode($publicUrl);

$formatValue = function ($value) {
    $value = trim((string)$value);
    return $value === '' ? '<span class="sx-bill-muted">Не указано</span>' : Html::encode($value);
};

$entityLink = function ($controllerId, $entity, $title, $subtitle = '', $icon = 'fa fa-link', $fallbackTitle = '') use ($formatValue) {
    $entityTitle = trim((string)$fallbackTitle);
    if (!$entity) {
        $value = $entityTitle !== '' ? Html::encode($entityTitle) : '<span class="sx-bill-muted">Не указано</span>';
        $subtitleHtml = $subtitle ? '<div class="sx-bill-entity-subtitle">'.$formatValue($subtitle).'</div>' : '';
        return '<div class="sx-bill-entity is-empty"><div class="sx-bill-entity-icon"><i class="'.$icon.'"></i></div><div><div class="sx-bill-entity-label">'.Html::encode($title).'</div><div class="sx-bill-entity-title">'.$value.'</div>'.$subtitleHtml.'</div></div>';
    }

    if ($entityTitle === '') {
        $entityTitle = $entity->asText;
    }

    $content = '<div class="sx-bill-entity">'
        . '<div class="sx-bill-entity-icon"><i class="'.$icon.'"></i></div>'
        . '<div class="sx-bill-entity-body">'
        . '<div class="sx-bill-entity-label">'.Html::encode($title).'</div>'
        . '<div class="sx-bill-entity-title">'.Html::encode($entityTitle).'</div>';

    if ($subtitle) {
        $content .= '<div class="sx-bill-entity-subtitle">'.$formatValue($subtitle).'</div>';
    }

    $content .= '</div></div>';

    return AjaxControllerActionsWidget::widget([
        'controllerId'            => $controllerId,
        'modelId'                 => $entity->id,
        'isRunFirstActionOnClick' => true,
        'content'                 => $content,
        'options'                 => [
            'class' => 'sx-bill-entity-link',
        ],
    ]);
};

$relatedPill = static function ($controllerId, $entity, $title, $subtitle, $icon, $class = '', array $colors = []) {
    $style = '';
    if ($colors) {
        $style = 'color: '.Html::encode($colors['text'])
            .'; background: '.Html::encode($colors['background'])
            .'; border-color: '.Html::encode($colors['border']).';';
    }

    $content = '<span class="sx-bill-related-pill '.$class.'" style="'.$style.'">'
        .'<i class="'.$icon.'"></i>'
        .'<span><strong>'.Html::encode($title).'</strong>';

    if ($subtitle) {
        $content .= '<small>'.Html::encode($subtitle).'</small>';
    }

    $content .= '</span></span>';

    return AjaxControllerActionsWidget::widget([
        'controllerId'            => $controllerId,
        'modelId'                 => $entity->id,
        'isRunFirstActionOnClick' => true,
        'content'                 => $content,
        'options'                 => [
            'class' => 'sx-bill-related-link',
        ],
    ]);
};

$payments = $model->payments;
$documents = $model->documents;

$billItems = $model->printableBillItems;
$hasItemDiscounts = false;
foreach ($billItems as $billItem) {
    if ((float)$billItem->discount_amount > 0) {
        $hasItemDiscounts = true;
        break;
    }
}
$hasBillDiscount = (float)$model->discount_amount > 0;
$hasDiscounts = $hasItemDiscounts || $hasBillDiscount;
$billItemsSubtotal = 0;
foreach ($billItems as $billItem) {
    $billItemsSubtotal += (float)$billItem->price * (float)$billItem->quantity;
}
$billItemsSubtotalMoney = new \skeeks\cms\money\Money($billItemsSubtotal, (string)$model->currency_code);
$billDiscountMoney = new \skeeks\cms\money\Money($model->discount_amount, (string)$model->currency_code);

$this->registerCss(<<<CSS
.sx-bill-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px 12px;
    margin-bottom: 0;
    padding: 12px 28px;
    border-bottom: 1px solid #edf0f2;
}
.sx-bill-actions-main {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-left: auto;
}
.sx-bill-actions .btn i {
    margin-right: 5px;
}
.sx-bill-related {
    display: flex;
    flex: 1 1 auto;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.sx-bill-related-link {
    display: inline-flex;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
}
.sx-bill-related-link:hover,
.sx-bill-related-link:focus {
    color: inherit;
    text-decoration: none;
    outline: none;
}
.sx-bill-related-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 7px 11px;
    border: 1px solid #dbe2e8;
    border-radius: 7px;
    background: #f8fafb;
    color: #34424e;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.sx-bill-related-pill.is-payment {
    color: #18703a;
    border-color: #cce8d6;
    background: #eaf7ef;
}
.sx-bill-related-pill.is-closed {
    color: #18703a;
    border-color: #cce8d6;
    background: #eaf7ef;
}
.sx-bill-related-pill.is-open {
    color: #806000;
    border-color: #f0dfa7;
    background: #fff7df;
}
.sx-bill-related-link:hover .sx-bill-related-pill,
.sx-bill-related-link:focus .sx-bill-related-pill {
    box-shadow: 0 5px 16px rgba(31, 82, 130, .1);
    transform: translateY(-1px);
}
.sx-bill-related-pill > i {
    flex: 0 0 auto;
}
.sx-bill-related-pill span {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
}
.sx-bill-related-pill strong {
    font-size: 13px;
    font-weight: 600;
}
.sx-bill-related-pill small {
    margin-top: 3px;
    color: inherit;
    font-size: 11px;
    opacity: .78;
}
.sx-bill-card-footer-actions {
    display: flex;
    justify-content: flex-end;
}
.sx-bill-action-form {
    margin: 0;
}
.sx-bill-card {
    background: #fff;
    border: 1px solid #e3e7eb;
    border-radius: 10px;
    overflow: hidden;
}
.sx-bill-section {
    padding: 22px 28px;
    border-bottom: 1px solid #edf0f2;
}
.sx-bill-section:last-child {
    border-bottom: 0;
}
.sx-bill-section-title {
    margin: 0 0 14px;
    font-size: 18px;
    font-weight: 600;
}
.sx-bill-entities {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.sx-bill-entity,
.sx-bill-entity-link {
    box-sizing: border-box;
    display: block;
    color: inherit;
    text-decoration: none;
}
.sx-bill-entity-link {
    height: 100%;
    cursor: pointer;
}
.sx-bill-entity {
    box-sizing: border-box;
    height: 100%;
    width: 100%;
    min-height: 80px;
    padding: 14px;
    border: 1px solid #e3e7eb;
    border-radius: 8px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    background: #fff;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.sx-bill-entity-link:hover,
.sx-bill-entity-link:focus {
    color: inherit;
    text-decoration: none;
    outline: none;
}
.sx-bill-entity-link:hover .sx-bill-entity,
.sx-bill-entity-link:focus .sx-bill-entity {
    border-color: #9dc8f0;
    box-shadow: 0 8px 24px rgba(31, 82, 130, .08);
}
.sx-bill-entity-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #eef3f7;
    color: #607080;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    font-size: 15px;
}
.sx-bill-entity-label {
    color: #8a929a;
    font-size: 12px;
    margin-bottom: 4px;
}
.sx-bill-entity-title {
    font-weight: 600;
}
.sx-bill-entity-subtitle {
    color: #606a73;
    font-size: 13px;
    margin-top: 4px;
}
.sx-bill-muted {
    color: #a5adb5;
}
.sx-bill-comment {
    margin: 0;
    color: #4d5963;
    white-space: pre-wrap;
}
.sx-bill-items {
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}
.sx-bill-items-wrap {
    width: 100%;
    overflow-x: auto;
}
.sx-bill-items th,
.sx-bill-items td {
    border-bottom: 1px solid #e3e7eb;
    padding: 12px 10px;
    vertical-align: top;
    white-space: nowrap;
}
.sx-bill-items th {
    color: #6d767f;
    font-weight: 600;
    background: #f8fafb;
}
.sx-bill-items tr:last-child td {
    border-bottom: 0;
}
.sx-bill-items-number {
    width: 48px;
    color: #9aa3ab;
}
.sx-bill-items-money {
    white-space: nowrap;
    text-align: right;
}
.sx-bill-items-name {
    min-width: 420px;
}
.sx-bill-summary {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e3e7eb;
}
.sx-bill-summary-row {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    color: #4d5963;
    font-size: 16px;
    line-height: 1.45;
}
.sx-bill-summary-label {
    color: #6d767f;
}
.sx-bill-summary-value {
    min-width: 150px;
    text-align: right;
    white-space: nowrap;
}
.sx-bill-summary-discount .sx-bill-summary-value {
    color: #6d767f;
}
.sx-bill-total {
    margin-top: 10px;
    justify-content: flex-end;
    color: #212529;
    font-size: 24px;
    font-weight: 600;
}
.sx-bill-requisites {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.sx-bill-requisite {
    padding: 12px;
    border-radius: 8px;
    background: #f8fafb;
}
.sx-bill-requisite-label {
    color: #8a929a;
    font-size: 12px;
    margin-bottom: 4px;
}
.sx-bill-requisite-value {
    font-weight: 600;
    overflow-wrap: anywhere;
}
@media (max-width: 900px) {
    .sx-bill-actions {
        align-items: stretch;
        flex-direction: column;
    }
    .sx-bill-actions-main {
        justify-content: flex-start;
        margin-left: 0;
    }
    .sx-bill-related {
        width: 100%;
    }
    .sx-bill-entities,
    .sx-bill-requisites {
        grid-template-columns: 1fr;
    }
}
@media print {
    .sx-bill-actions {
        display: none;
    }
    .sx-bill-view {
        max-width: none;
    }
    .sx-bill-card {
        border: 0;
        border-radius: 0;
    }
}
CSS
);

$this->registerJs(<<<JS
(function() {
    var publicUrl = {$publicUrlJson};
    var restoreText = function(button, text) {
        setTimeout(function() {
            button.html(text);
        }, 1800);
    };
    $(document).on("click", "[data-sx-bill-share]", function() {
        var button = $(this);
        var oldText = button.html();
        var done = function() {
            button.html('<i class="fa fa-check"></i> Ссылка скопирована');
            restoreText(button, oldText);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(publicUrl).then(done);
            return false;
        }

        var input = $('<input type="text" />').val(publicUrl).appendTo('body');
        input[0].select();
        document.execCommand('copy');
        input.remove();
        done();
        return false;
    });
})();
JS
);

?>

<div class="sx-bill-view">
    <div class="sx-bill-card">
        <div class="sx-bill-actions">
            <?php if ($payments || $documents) : ?>
                <div class="sx-bill-related">
                    <?php if ($documents) : ?>
                        <span class="sx-bill-related-pill <?= $model->isClosedByDocuments ? 'is-closed' : 'is-open'; ?>">
                            <i class="fa <?= $model->isClosedByDocuments ? 'fa-check' : 'fa-clock'; ?>"></i>
                            <span>
                                <strong><?= $model->isClosedByDocuments ? 'Закрыт документами' : 'Не закрыт документами'; ?></strong>
                                <?php if (!$model->isClosedByDocuments) : ?>
                                    <small>Осталось закрыть: <?= Html::encode((string)$model->documentBalanceMoney); ?></small>
                                <?php endif; ?>
                            </span>
                        </span>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment) : ?>
                        <?= $relatedPill(
                            '/shop/admin-payment',
                            $payment,
                            'Платеж №'.$payment->id,
                            (string)$payment->money.' · '.Yii::$app->formatter->asDate($payment->created_at),
                            'fa fa-check',
                            'is-payment'
                        ); ?>
                    <?php endforeach; ?>
                    <?php foreach ($documents as $document) : ?>
                        <?= $relatedPill(
                            '/cms/admin-cms-document',
                            $document,
                            $document->typeAsText.' №'.$document->number,
                            $document->statusAsText.' · '.Yii::$app->formatter->asDate($document->issued_at ?: $document->created_at),
                            $document->statusIcon,
                            '',
                            $document->statusColors
                        ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="sx-bill-actions-main">
                <button type="button" class="btn btn-default" data-sx-bill-share>
                    <i class="fa fa-link"></i> Поделиться
                </button>
                <a href="<?= Html::encode($publicUrl); ?>" class="btn btn-default" target="_blank">
                    <i class="fa fa-external-link-alt"></i> Открыть
                </a>
                <a href="<?= Html::encode($pdfUrl); ?>" class="btn btn-default" target="_blank">
                    <i class="fa fa-file-pdf"></i> Скачать PDF
                </a>
                <?php if (\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) : ?>
                    <a href="<?= Html::encode($pdfNoSignatureUrl); ?>" class="btn btn-default" target="_blank">
                        <i class="fa fa-file-download"></i> PDF без подписей
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="sx-bill-section">
            <h3 class="sx-bill-section-title">Участники и реквизиты</h3>
            <div class="sx-bill-entities">
                <?= $entityLink('/cms/admin-cms-company', $model->company, 'Компания', '', 'fa fa-building', $model->billCompanyName); ?>
                <?= $entityLink('/cms/admin-cms-contractor', $model->senderContractor, 'Плательщик', $model->billSenderInn ? 'ИНН '.$model->billSenderInn : '', 'fa fa-user', $model->billSenderName); ?>
                <?= $entityLink('/cms/admin-cms-contractor', $model->receiverContractor, 'Получатель', $model->billReceiverInn ? 'ИНН '.$model->billReceiverInn : '', 'fa fa-briefcase', $model->billReceiverName); ?>
                <?= $entityLink('/cms/admin-cms-contractor-bank', $model->receiverContractorBank, 'Банк получателя', $model->billReceiverBankBic ? 'БИК '.$model->billReceiverBankBic : '', 'fa fa-credit-card', $model->billReceiverBankName); ?>
            </div>
        </div>

        <?php if ($model->hasBillReceiverBankData) : ?>
            <div class="sx-bill-section">
                <h3 class="sx-bill-section-title">Банковские реквизиты</h3>
                <div class="sx-bill-requisites">
                    <div class="sx-bill-requisite">
                        <div class="sx-bill-requisite-label">Банк</div>
                        <div class="sx-bill-requisite-value"><?= $formatValue($model->billReceiverBankName); ?></div>
                    </div>
                    <div class="sx-bill-requisite">
                        <div class="sx-bill-requisite-label">БИК</div>
                        <div class="sx-bill-requisite-value"><?= $formatValue($model->billReceiverBankBic); ?></div>
                    </div>
                    <div class="sx-bill-requisite">
                        <div class="sx-bill-requisite-label">Корр. счет</div>
                        <div class="sx-bill-requisite-value"><?= $formatValue($model->billReceiverBankCorrespondentAccount); ?></div>
                    </div>
                    <div class="sx-bill-requisite">
                        <div class="sx-bill-requisite-label">Расчетный счет</div>
                        <div class="sx-bill-requisite-value"><?= $formatValue($model->billReceiverBankCheckingAccount); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($model->description) : ?>
            <div class="sx-bill-section">
                <h3 class="sx-bill-section-title">Комментарий</h3>
                <p class="sx-bill-comment"><?= Html::encode($model->description); ?></p>
            </div>
        <?php endif; ?>

        <div class="sx-bill-section">
            <h3 class="sx-bill-section-title">Позиции счета</h3>
            <div class="sx-bill-items-wrap">
                <table class="sx-bill-items">
                    <thead>
                        <tr>
                            <th class="sx-bill-items-number">№</th>
                            <th class="sx-bill-items-name">Наименование</th>
                            <th>Кол-во</th>
                            <th>Ед.</th>
                            <th class="sx-bill-items-money">Цена</th>
                            <?php if ($hasDiscounts) : ?>
                                <th class="sx-bill-items-money">Скидка</th>
                            <?php endif; ?>
                            <th>НДС</th>
                            <th class="sx-bill-items-money">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($billItems as $index => $item) : ?>
                            <tr>
                                <td class="sx-bill-items-number"><?= $index + 1; ?></td>
                                <td class="sx-bill-items-name"><?= Html::encode($item->name); ?></td>
                                <td><?= (float)$item->quantity; ?></td>
                                <td><?= Html::encode($item->measure_name); ?></td>
                                <td class="sx-bill-items-money"><?= Html::encode((string)$item->priceMoney); ?></td>
                                <?php if ($hasDiscounts) : ?>
                                    <td class="sx-bill-items-money">
                                        <?= (float)$item->discount_amount > 0 ? Html::encode((string)$item->discountMoney) : ''; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= Html::encode($item->vat_name ?: 'Без НДС'); ?></td>
                                <td class="sx-bill-items-money"><?= Html::encode((string)$item->money); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="sx-bill-summary">
                <div class="sx-bill-summary-row">
                    <span class="sx-bill-summary-label">Предытог</span>
                    <span class="sx-bill-summary-value"><?= Html::encode((string)$billItemsSubtotalMoney); ?></span>
                </div>
                <?php if ($hasBillDiscount) : ?>
                    <div class="sx-bill-summary-row sx-bill-summary-discount">
                        <span class="sx-bill-summary-label">Скидка</span>
                        <span class="sx-bill-summary-value">-<?= Html::encode((string)$billDiscountMoney); ?></span>
                    </div>
                <?php endif; ?>
                <div class="sx-bill-summary-row sx-bill-total">
                    <span class="sx-bill-summary-label">Итого</span>
                    <span class="sx-bill-summary-value"><?= Html::encode((string)$model->money); ?></span>
                </div>
            </div>
        </div>

        <?php if ($model->deals) : ?>
            <div class="sx-bill-section">
                <h3 class="sx-bill-section-title">Связи</h3>
                <div class="sx-bill-entities">
                    <?php foreach ($model->deals as $deal) : ?>
                        <?= $entityLink('/cms/admin-cms-deal', $deal, 'Сделка', '', 'fa fa-file'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$model->paid_at && !$model->closed_at) : ?>
            <div class="sx-bill-section sx-bill-card-footer-actions">
                <?= Html::beginForm($closeUrl, 'post', ['class' => 'sx-bill-action-form']); ?>
                    <?= Html::submitButton('<i class="fa fa-ban"></i> Отменить счет', [
                        'class' => 'btn btn-default',
                        'data' => [
                            'confirm' => 'Отменить этот счет?',
                        ],
                    ]); ?>
                <?= Html::endForm(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
