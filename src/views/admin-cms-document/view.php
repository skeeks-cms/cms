<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\shop\models\ShopDocument */

use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\shop\models\ShopDocument;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$publicUrl = $model->getUrl(true);
$pdfUrl = $model->getPdfUrl(true);
$pdfNoSignatureUrl = Url::to(['/shop/shop-document/pdf', 'code' => $model->code, 'noSignature' => '1'], true);
$publicUrlJson = Json::htmlEncode($publicUrl);
$statusColors = $model->statusColors;
$statusStyle = Html::cssStyleFromArray([
    'border-color' => $statusColors['border'],
    'background'   => $statusColors['background'],
    'color'        => $statusColors['text'],
]);

$formatValue = function ($value) {
    $value = trim((string)$value);
    return $value === '' ? '<span class="sx-document-muted">Не указано</span>' : Html::encode($value);
};

$entityLink = function ($controllerId, $entity, $title, $subtitle = '', $icon = 'fa fa-link', $fallbackTitle = '') use ($formatValue) {
    $entityTitle = trim((string)$fallbackTitle);
    if (!$entity) {
        $value = $entityTitle !== '' ? Html::encode($entityTitle) : '<span class="sx-document-muted">Не указано</span>';
        $subtitleHtml = $subtitle ? '<div class="sx-document-entity-subtitle">'.$formatValue($subtitle).'</div>' : '';
        return '<div class="sx-document-entity is-empty"><div class="sx-document-entity-icon"><i class="'.$icon.'"></i></div><div><div class="sx-document-entity-label">'.Html::encode($title).'</div><div class="sx-document-entity-title">'.$value.'</div>'.$subtitleHtml.'</div></div>';
    }

    if ($entityTitle === '') {
        $entityTitle = $entity->asText;
    }

    $content = '<div class="sx-document-entity">'
        . '<div class="sx-document-entity-icon"><i class="'.$icon.'"></i></div>'
        . '<div class="sx-document-entity-body">'
        . '<div class="sx-document-entity-label">'.Html::encode($title).'</div>'
        . '<div class="sx-document-entity-title">'.Html::encode($entityTitle).'</div>';

    if ($subtitle) {
        $content .= '<div class="sx-document-entity-subtitle">'.$formatValue($subtitle).'</div>';
    }

    $content .= '</div></div>';

    return AjaxControllerActionsWidget::widget([
        'controllerId'            => $controllerId,
        'modelId'                 => $entity->id,
        'isRunFirstActionOnClick' => true,
        'content'                 => $content,
        'options'                 => [
            'class' => 'sx-document-entity-link',
        ],
    ]);
};

$crmClientEntity = $model->company
    ? $entityLink('/cms/admin-cms-company', $model->company, 'Компания', '', 'fa fa-building')
    : $entityLink('/cms/admin-user', $model->cmsUser, 'Клиент', '', 'fa fa-user');

$items = $model->documentItems;
$itemsSubtotal = 0;
$hasDiscounts = false;
foreach ($items as $item) {
    $itemsSubtotal += (float)$item->price * (float)$item->quantity;
    if ((float)$item->discount_amount > 0) {
        $hasDiscounts = true;
    }
}
$itemsSubtotalMoney = new \skeeks\cms\money\Money($itemsSubtotal, (string)$model->currency_code);


$this->registerCss(<<<CSS
.sx-document-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px 12px;
    margin-bottom: 0;
    padding: 12px 28px;
    border-bottom: 1px solid #edf0f2;
}
.sx-document-actions-main {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.sx-document-actions .btn i {
    margin-right: 5px;
}
.sx-document-card {
    background: #fff;
    border: 1px solid #e3e7eb;
    border-radius: 10px;
    overflow: hidden;
}
.sx-document-section {
    padding: 22px 28px;
    border-bottom: 1px solid #edf0f2;
}
.sx-document-section:last-child {
    border-bottom: 0;
}
.sx-document-section-title {
    margin: 0 0 14px;
    font-size: 18px;
    font-weight: 600;
}
.sx-document-entities {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.sx-document-party-column {
    display: grid;
    align-content: start;
    gap: 12px;
}
.sx-document-entity,
.sx-document-entity-link {
    box-sizing: border-box;
    display: block;
    color: inherit;
    text-decoration: none;
}
.sx-document-entity-link {
    height: 100%;
    cursor: pointer;
}
.sx-document-entity {
    height: 100%;
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
.sx-document-entity-link:hover,
.sx-document-entity-link:focus {
    color: inherit;
    text-decoration: none;
    outline: none;
}
.sx-document-entity-link:hover .sx-document-entity,
.sx-document-entity-link:focus .sx-document-entity {
    border-color: #9dc8f0;
    box-shadow: 0 8px 24px rgba(31, 82, 130, .08);
}
.sx-document-entity-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #eef3f7;
    color: #607080;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}
.sx-document-entity-label {
    color: #8a929a;
    font-size: 12px;
    margin-bottom: 4px;
}
.sx-document-entity-title {
    font-weight: 600;
}
.sx-document-entity-subtitle,
.sx-document-muted {
    color: #8a929a;
}
.sx-document-comment {
    margin: 0;
    color: #4d5963;
    white-space: pre-wrap;
}
.sx-document-items {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}
.sx-document-items-wrap {
    width: 100%;
    overflow-x: auto;
}
.sx-document-items th,
.sx-document-items td {
    border-bottom: 1px solid #e3e7eb;
    padding: 12px 10px;
    vertical-align: top;
    white-space: nowrap;
}
.sx-document-items th {
    color: #6d767f;
    font-weight: 600;
    background: #f8fafb;
}
.sx-document-items-name {
    min-width: 420px;
}
.sx-document-items-money {
    text-align: right;
}
.sx-document-summary {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e3e7eb;
}
.sx-document-summary-row {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    color: #4d5963;
    font-size: 16px;
    line-height: 1.45;
}
.sx-document-total {
    margin-top: 10px;
    color: #212529;
    font-size: 24px;
    font-weight: 600;
}
.sx-document-status-panel {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    padding: 18px 20px;
    border: 1px solid;
    border-radius: 8px;
}
.sx-document-status-current {
    display: grid;
    gap: 5px;
}
.sx-document-status-label {
    color: inherit;
    font-size: 12px;
    opacity: .72;
}
.sx-document-status-value {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 20px;
    font-weight: 600;
}
.sx-document-status-icon {
    width: 16px;
    text-align: center;
    flex: 0 0 auto;
}
.sx-document-status-note {
    color: inherit;
    opacity: .76;
}
.sx-document-status-reason {
    margin-top: 3px;
    white-space: pre-wrap;
}
.sx-document-status-change {
    flex: 0 0 auto;
}
.sx-document-status-change i {
    margin-right: 5px;
}
.sx-document-status-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}
@media (max-width: 900px) {
    .sx-document-actions {
        align-items: stretch;
        flex-direction: column;
    }
    .sx-document-actions-main {
        justify-content: flex-start;
    }
    .sx-document-entities {
        grid-template-columns: 1fr;
    }
    .sx-document-status-change {
        width: 100%;
    }
}
@media print {
    .sx-document-actions {
        display: none;
    }
    .sx-document-card {
        border: 0;
        border-radius: 0;
    }
}
CSS
);

$this->registerJs(<<<JS
(function() {
    var publicUrl = {$publicUrlJson};
    $(document).on("click", "[data-sx-document-share]", function() {
        var button = $(this);
        var oldText = button.html();
        var done = function() {
            button.html('<i class="fa fa-check"></i> Ссылка скопирована');
            setTimeout(function() { button.html(oldText); }, 1800);
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

    var toggleCanceledReason = function() {
        var isCanceled = $("#sx-document-status-select").val() === "canceled";
        $("#sx-document-cancel-reason-group").toggle(isCanceled);
        $("#sx-document-cancel-reason").prop("required", isCanceled);
    };

    $(document).on("change", "#sx-document-status-select", toggleCanceledReason);
    $(document).on("shown.bs.modal", "#sx-document-status-modal", toggleCanceledReason);
    toggleCanceledReason();
})();
JS
);

?>

<div class="sx-document-view">
    <div class="sx-document-card">
        <div class="sx-document-actions">
            <div class="sx-document-actions-main">
                <button type="button" class="btn btn-default" data-sx-document-share>
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
                <?php if ($model->type == \skeeks\cms\shop\models\ShopDocument::TYPE_UPD) : ?>
                    <a href="<?= Html::encode(Url::to(['xml', 'pk' => $model->id])); ?>" class="btn btn-default" target="_blank">
                        <i class="fa fa-file-code"></i> Скачать XML
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="sx-document-section">
            <div class="sx-document-status-panel" style="<?= Html::encode($statusStyle); ?>">
                <div class="sx-document-status-current">
                    <div class="sx-document-status-label">Статус документа</div>
                    <div class="sx-document-status-value">
                        <i class="sx-document-status-icon <?= Html::encode($model->statusIcon); ?>"></i>
                        <?= Html::encode($model->statusAsText); ?>
                    </div>
                    <?php if ($model->status === ShopDocument::STATUS_CANCELED && $model->canceled_reason) : ?>
                        <div class="sx-document-status-reason"><strong>Причина отмены:</strong> <?= nl2br(Html::encode($model->canceled_reason)); ?></div>
                    <?php elseif (!$model->isEditable) : ?>
                        <div class="sx-document-status-note">Документ нельзя редактировать в этом статусе.</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-default sx-document-status-change" data-toggle="modal" data-target="#sx-document-status-modal">
                    <i class="fa fa-edit"></i> Изменить статус
                </button>
            </div>
        </div>

        <?php if ($model->bills || $model->deals) : ?>
            <div class="sx-document-section">
                <h3 class="sx-document-section-title">Основание</h3>
                <div class="sx-document-entities">
                    <?php foreach ($model->bills as $bill) : ?>
                        <?= $entityLink('/cms/admin-cms-bill', $bill, 'Счет', '', 'fa fa-file'); ?>
                    <?php endforeach; ?>
                    <?php foreach ($model->deals as $deal) : ?>
                        <?= $entityLink('/cms/admin-cms-deal', $deal, 'Сделка', '', 'fa fa-file'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="sx-document-section">
            <h3 class="sx-document-section-title">Стороны</h3>
            <div class="sx-document-entities sx-document-parties">
                <div class="sx-document-party-column">
                    <?= $entityLink('/cms/admin-cms-contractor', $model->sellerContractor, 'Продавец / исполнитель', $model->sellerInn ? 'ИНН '.$model->sellerInn : '', 'fa fa-briefcase', $model->sellerName); ?>
                </div>
                <div class="sx-document-party-column">
                    <?= $entityLink('/cms/admin-cms-contractor', $model->buyerContractor, 'Покупатель / заказчик', $model->buyerInn ? 'ИНН '.$model->buyerInn : '', 'fa fa-user', $model->buyerName); ?>
                    <?= $crmClientEntity; ?>
                </div>
            </div>
        </div>

        <?php if ($model->description || $model->comment_before || $model->comment_after) : ?>
            <div class="sx-document-section">
                <h3 class="sx-document-section-title">Комментарии</h3>
                <?php if ($model->description) : ?>
                    <p class="sx-document-comment"><?= Html::encode($model->description); ?></p>
                <?php endif; ?>
                <?php if ($model->comment_before) : ?>
                    <p class="sx-document-comment"><?= Html::encode($model->comment_before); ?></p>
                <?php endif; ?>
                <?php if ($model->comment_after) : ?>
                    <p class="sx-document-comment"><?= Html::encode($model->comment_after); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="sx-document-section">
            <h3 class="sx-document-section-title">Позиции документа</h3>
            <div class="sx-document-items-wrap">
                <table class="sx-document-items">
                    <thead>
                        <tr>
                            <th style="width: 48px;">№</th>
                            <th class="sx-document-items-name">Наименование</th>
                            <th>Кол-во</th>
                            <th>Ед.</th>
                            <th class="sx-document-items-money">Цена</th>
                            <?php if ($hasDiscounts) : ?>
                                <th class="sx-document-items-money">Скидка</th>
                            <?php endif; ?>
                            <th>НДС</th>
                            <th class="sx-document-items-money">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item) : ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td class="sx-document-items-name"><?= Html::encode($item->name); ?></td>
                                <td><?= (float)$item->quantity; ?></td>
                                <td><?= Html::encode($item->measure_name); ?></td>
                                <td class="sx-document-items-money"><?= Html::encode((string)$item->priceMoney); ?></td>
                                <?php if ($hasDiscounts) : ?>
                                    <td class="sx-document-items-money"><?= (float)$item->discount_amount > 0 ? Html::encode((string)$item->discountMoney) : ''; ?></td>
                                <?php endif; ?>
                                <td><?= Html::encode($item->vat_name ?: 'Без НДС'); ?></td>
                                <td class="sx-document-items-money"><?= Html::encode((string)$item->money); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="sx-document-summary">
                <div class="sx-document-summary-row">
                    <span>Предытог</span>
                    <span><?= Html::encode((string)$itemsSubtotalMoney); ?></span>
                </div>
                <div class="sx-document-summary-row sx-document-total">
                    <span>Итого</span>
                    <span><?= Html::encode((string)$model->money); ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php Modal::begin([
    'id'     => 'sx-document-status-modal',
    'header' => '<h4 class="modal-title">Изменить статус документа</h4>',
]); ?>
<?= Html::beginForm(Url::to(['status', 'pk' => $model->id]), 'post', ['id' => 'sx-document-status-form']); ?>
<div class="form-group">
    <label for="sx-document-status-select">Новый статус</label>
    <?= Html::dropDownList('status', $model->status, ShopDocument::optionsForStatus(), [
        'id'    => 'sx-document-status-select',
        'class' => 'form-control',
    ]); ?>
</div>
<div class="form-group" id="sx-document-cancel-reason-group">
    <label for="sx-document-cancel-reason">Причина отмены <span class="text-danger">*</span></label>
    <?= Html::textarea(
        'canceled_reason',
        $model->status === ShopDocument::STATUS_CANCELED ? $model->canceled_reason : '',
        [
            'id'          => 'sx-document-cancel-reason',
            'class'       => 'form-control',
            'rows'        => 4,
            'placeholder' => 'Укажите, почему документ отменен',
        ]
    ); ?>
    <div class="help-block">Причина будет сохранена в карточке и истории документа.</div>
</div>
<div class="sx-document-status-modal-actions">
    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
    <?= Html::submitButton('<i class="fa fa-check"></i> Сохранить статус', ['class' => 'btn btn-primary']); ?>
</div>
<?= Html::endForm(); ?>
<?php Modal::end(); ?>
