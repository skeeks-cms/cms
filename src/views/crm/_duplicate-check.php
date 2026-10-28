<?php
/**
 * Duplicate check while a company or a contact is being filled in
 * (CrmDuplicateCheckActions, П2а): no separate search — the usual fields are
 * watched, and «уже есть, ответственный …» appears under them by itself.
 * A line pasted into the name («Ромашка, +7 …, info@…») is split into the
 * fields. For a company this block also holds its first phones and emails.
 *
 * @var yii\web\View $this
 * @var string $for company|client
 * @var string $url the duplicate-check action of the controller
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\crm\CrmDuplicateGuard;
use yii\helpers\Html;

LeadIdentityAsset::register($this);
$isCompany = $for === 'company';
// The form fields that are checked: the name, the phones, the emails.
$watch = $isCompany
    ? ['name' => '[name="CmsCompany[name]"]', 'phones' => '[data-sx-dupcheck-phones]', 'emails' => '[data-sx-dupcheck-emails]']
    : ['name' => '[name="CmsUser[last_name]"], [name="CmsUser[first_name]"]', 'phones' => '[name="CmsUser[phone]"]', 'emails' => '[name="CmsUser[email]"]'];
?>
<div data-sx-dupcheck="<?= Html::encode($for); ?>" data-match-url="<?= Html::encode($url); ?>" data-watch="<?= Html::encode(\yii\helpers\Json::encode($watch)); ?>">
    <?php if ($isCompany) : ?>
        <div class="form-group">
            <label class="control-label">Телефон</label>
            <?= Html::textInput('CrmCardContacts[phones]', '', ['class' => 'form-control', 'placeholder' => '+7 900 000-00-00', 'data-sx-dupcheck-phones' => true]); ?>
        </div>
        <div class="form-group">
            <label class="control-label">Email</label>
            <?= Html::textInput('CrmCardContacts[emails]', '', ['class' => 'form-control', 'placeholder' => 'info@company.ru', 'data-sx-dupcheck-emails' => true]); ?>
        </div>
    <?php endif; ?>
    <div class="sx-lead-identity__results sx-crm-dupcheck__results" data-sx-dupcheck-results hidden aria-live="polite"></div>
    <?php if ($isCompany) : ?>
        <?php // A strong match: the field appears with it, required in the strict mode only (CrmDuplicateGuard). ?>
        <div class="form-group" data-sx-dupcheck-reason hidden>
            <label class="control-label"><?= Html::encode(CrmDuplicateGuard::reasonLabel()); ?></label>
            <?= Html::textarea('CrmCardContacts[duplicate_reason]', '', ['class' => 'form-control', 'rows' => 2,
                'placeholder' => 'Например: другое юрлицо того же владельца, другой город.'
                    .(CrmDuplicateGuard::isStrict() ? ' Без причины компания не сохранится — откройте существующую.' : ''),
            ]); ?>
        </div>
    <?php endif; ?>
</div>
