<?php
/**
 * The dropdown of the field "Компания" or "Контакт" (П2а, as in Bitrix24):
 * records found by CrmMatcher and, always last, «+ создать новую …» from the
 * typed line. Rows only fill the identity form (lead-identity.js).
 *
 * @var array[] $candidates presented candidates (CrmAccess::presentCandidate())
 * @var string $for company|client
 * @var array $parsed CrmContactParser::parse() of the typed line
 * @var bool $allowForeign a client of another responsible may be taken with a request
 */

use skeeks\cms\crm\CrmMatcher;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$isCompany = $for === CrmMatcher::TYPE_COMPANY;
$reasonText = static function (array $reason): string {
    $labels = [
        CrmMatcher::BY_EMAIL => 'e-mail',
        CrmMatcher::BY_PHONE => 'телефон',
        CrmMatcher::BY_INN => 'ИНН',
        CrmMatcher::BY_DOMAIN => 'домен',
        CrmMatcher::BY_NAME => 'название',
    ];

    return ($labels[$reason['by']] ?? $reason['by']).' '.$reason['value'];
};
// A contact search: a typed text is the person's name.
$name = $isCompany ? trim((string)$parsed['name']) : trim((string)($parsed['name'] ?: $parsed['person']));
$details = array_merge($parsed['phones'], $parsed['emails']);
$create = Json::encode($parsed + ['title' => $name]);
?>
<?php if ($candidates) : ?>
<ul class="sx-lead-drop sx-lead-drop--found" role="listbox">
    <?php foreach ($candidates as $candidate) : ?>
        <?php
        $responsible = implode(', ', array_column($candidate['responsible'], 'name'));
        $meta = ($candidate['strong'] ? 'Совпал ' : 'Похоже: ').implode(', ', array_map($reasonText, $candidate['reasons']));
        ?>
        <?php if ($candidate['visible']) : ?>
            <li class="sx-lead-drop__item" role="option" tabindex="-1"
                data-sx-identity-pick="<?= Html::encode($for); ?>"
                data-id="<?= (int)$candidate['id']; ?>"
                data-title="<?= Html::encode((string)$candidate['title']); ?>"
                data-companies="<?= Html::encode(Json::encode($candidate['companies'] ?? [])); ?>">
                <span class="sx-lead-drop__title"><?= Html::encode((string)$candidate['title']); ?></span>
                <span class="sx-lead-drop__meta"><?= Html::encode($meta); ?><?= $responsible !== '' ? ' · '.Html::encode($responsible) : ''; ?></span>
            </li>
        <?php else : ?>
            <?php // A client of another responsible: its name only for a company, the contacts are not shown. ?>
            <li class="sx-lead-drop__item is-hidden">
                <span class="sx-lead-drop__title"><?= Html::encode($isCompany ? 'Такая компания уже есть: «'.$candidate['title'].'»' : 'Такой контакт уже есть в базе'); ?></span>
                <span class="sx-lead-drop__meta"><?= Html::encode($meta); ?> · <?= $responsible !== '' ? 'ответственный '.Html::encode($responsible) : 'ответственного нет'; ?></span>
                <span class="sx-lead-drop__actions">
                    <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity-foreign-actions', [
                        'candidate' => $candidate, 'for' => $for, 'allowForeign' => $allowForeign,
                        'requestUrl' => Url::to(['/cms/admin-cms-lead/work-request']), 'create' => $create,
                    ]); ?>
                </span>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
<?php // «Создать» stays in sight under a long list. ?>
<ul class="sx-lead-drop sx-lead-drop--create" role="listbox">
    <?php if (!$isCompany && $name === '' && $details) : ?>
        <li class="sx-lead-drop__item sx-lead-drop__item--create" role="option" tabindex="-1"
            data-sx-identity-create="lead-contacts" data-create="<?= Html::encode($create); ?>">
            <span class="sx-lead-drop__title">Без контакта — оставить в сделке <?= Html::encode(implode(', ', $details)); ?></span>
        </li>
    <?php endif; ?>
    <li class="sx-lead-drop__item sx-lead-drop__item--create" role="option" tabindex="-1"
        data-sx-identity-create="<?= Html::encode($for); ?>" data-create="<?= Html::encode($create); ?>">
        <span class="sx-lead-drop__title">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <?= $isCompany ? 'Создать новую компанию' : 'Создать новый контакт'; ?><?= $name !== '' ? ' «'.Html::encode($name).'»' : ''; ?>
        </span>
        <?php if ($details) : ?>
            <span class="sx-lead-drop__meta"><?= Html::encode(implode(', ', $details)); ?></span>
        <?php endif; ?>
    </li>
    <?php if ($isCompany && $name !== '' && $parsed['person'] !== '') : ?>
        <li class="sx-lead-drop__item sx-lead-drop__item--create" role="option" tabindex="-1"
            data-sx-identity-create="company-and-client" data-create="<?= Html::encode($create); ?>">
            <span class="sx-lead-drop__title">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Создать компанию «<?= Html::encode($name); ?>» с контактом «<?= Html::encode($parsed['person']); ?>»
            </span>
        </li>
    <?php endif; ?>
</ul>
