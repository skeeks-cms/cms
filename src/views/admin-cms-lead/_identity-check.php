<?php
/**
 * «Есть совпадения: N» next to a new company or contact of the deal form
 * (П2а, as in Bitrix24): its name, phone and email checked by CrmMatcher
 * while typed, grouped by what matched. An own record is used instead of the
 * new one; a client of another responsible offers the requests and «Это
 * другая компания» (the save then needs a reason, CrmDuplicateGuard).
 * Contacts are shown only for records the employee sees.
 *
 * @var array[] $candidates presented candidates (CrmAccess::presentCandidate())
 * @var string $for company|client
 * @var bool $allowForeign a client of another responsible may be taken with a request
 */

use skeeks\cms\crm\CrmMatcher;
use skeeks\cms\crm\CrmProtection;
use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

if (!$candidates) {
    return;
}
$isCompany = $for === CrmMatcher::TYPE_COMPANY;
$groups = [
    CrmMatcher::BY_PHONE => 'по телефону',
    CrmMatcher::BY_EMAIL => 'по e-mail',
    CrmMatcher::BY_INN => 'по ИНН',
    CrmMatcher::BY_DOMAIN => 'по домену',
    CrmMatcher::BY_NAME => 'по названию',
];
$plural = static function (int $count): string {
    $mod10 = $count % 10;
    $mod100 = $count % 100;
    $word = $mod10 === 1 && $mod100 !== 11 ? 'совпадение'
        : ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) ? 'совпадения' : 'совпадений');

    return $count.' '.$word;
};
// Each record once, in the group of its strongest evidence (a phone before a name).
$byGroup = array_fill_keys(array_keys($groups), []);
foreach ($candidates as $candidate) {
    $found = array_column($candidate['reasons'], 'by');
    foreach (array_keys($groups) as $by) {
        if (in_array($by, $found, true)) {
            $byGroup[$by][] = $candidate;
            break;
        }
    }
}
$byGroup = array_filter($byGroup);
// Matched values in bold: phones by their digits, emails as typed in any case.
$matched = static function (array $candidate, string $value): bool {
    foreach ($candidate['reasons'] as $reason) {
        if ($reason['by'] === CrmMatcher::BY_PHONE && PhoneHelper::searchDigits($value) !== '' && PhoneHelper::searchDigits($value) === PhoneHelper::searchDigits((string)$reason['value'])) {
            return true;
        }
        if ($reason['by'] === CrmMatcher::BY_EMAIL && mb_strtolower(trim($value), 'UTF-8') === mb_strtolower(trim((string)$reason['value']), 'UTF-8')) {
            return true;
        }
    }

    return false;
};
$contacts = static function (array $candidate) use ($matched): array {
    if (!$candidate['visible']) {
        // A hidden record: only what was typed and matched.
        return array_map(static fn($reason) => [(string)$reason['value'], true], array_filter($candidate['reasons'],
            static fn($reason) => in_array($reason['by'], [CrmMatcher::BY_PHONE, CrmMatcher::BY_EMAIL], true)));
    }
    $values = [];
    if ($candidate['type'] === CrmMatcher::TYPE_COMPANY && ($company = CmsCompany::findOne((int)$candidate['id']))) {
        $values = array_merge(array_column($company->getPhones()->limit(3)->all(), 'value'), array_column($company->getEmails()->limit(3)->all(), 'value'));
    } elseif ($candidate['type'] === CrmMatcher::TYPE_CLIENT && ($client = CmsUser::findOne((int)$candidate['id']))) {
        $values = array_merge(array_column($client->getCmsUserPhones()->limit(3)->all(), 'value'), array_column($client->getCmsUserEmails()->limit(3)->all(), 'value'));
    }

    return array_map(static fn($value) => [(string)$value, $matched($candidate, (string)$value)], $values);
};
?>
<div class="sx-lead-check" role="dialog" aria-label="Есть совпадения">
    <div class="sx-lead-check__head">
        <span>Есть совпадения: <?= count($candidates); ?></span>
        <button type="button" class="sx-lead-check__close" data-sx-identity-check-close aria-label="Закрыть">&times;</button>
    </div>
    <?php foreach ($byGroup as $by => $list) : ?>
        <div class="sx-lead-check__group">
            <div class="sx-lead-check__group-title"><?= Html::encode($plural(count($list)).' '.$groups[$by]); ?></div>
            <?php foreach ($list as $candidate) : ?>
                <?php $responsible = implode(', ', array_column($candidate['responsible'], 'name')); ?>
                <div class="sx-lead-check__card<?= $candidate['visible'] ? '' : ' is-hidden'; ?>">
                    <span class="sx-lead-check__kind"><?= $isCompany ? 'Компания' : 'Контакт'; ?></span>
                    <?php if ($candidate['visible']) : ?>
                        <?= Html::a(Html::encode((string)$candidate['title']), [$isCompany ? '/cms/admin-cms-company/view' : '/cms/admin-user/view', 'pk' => (int)$candidate['id']], [
                            'class' => 'sx-lead-check__title',
                            'target' => '_blank',
                            'data-pjax' => 0,
                        ]); ?>
                    <?php elseif (!empty($candidate['inBase'])) : ?>
                        <span class="sx-lead-check__title"><?= $isCompany ? 'Компания в общей базе' : 'Контакт в общей базе'; ?></span>
                    <?php else : ?>
                        <span class="sx-lead-check__title"><?= Html::encode($isCompany ? (string)$candidate['title'] : 'Такой контакт уже есть в базе'); ?></span>
                    <?php endif; ?>
                    <?= CrmProtection::shieldIcon((string)($candidate['protection'] ?? ''), (string)($candidate['protectionText'] ?? '')); ?>
                    <?php if ($values = $contacts($candidate)) : ?>
                        <span class="sx-lead-check__contacts">
                            <?= implode(', ', array_map(static fn($item) => $item[1] ? Html::tag('b', Html::encode($item[0])) : Html::encode($item[0]), $values)); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (empty($candidate['inBase'])) : ?>
                        <span class="sx-lead-check__meta"><?= $responsible !== '' ? 'Ответственный: '.Html::encode($responsible) : 'Ответственного нет'; ?></span>
                    <?php endif; ?>
                    <span class="sx-lead-check__actions">
                        <?php if ($candidate['visible']) : ?>
                            <?= Html::button('Использовать', [
                                'class' => 'sx-button sx-button--secondary sx-button--sm',
                                'data-sx-identity-pick' => $for,
                                'data-id' => (int)$candidate['id'],
                                'data-title' => (string)$candidate['title'],
                                'data-companies' => Json::encode($candidate['companies'] ?? []),
                            ]); ?>
                        <?php else : ?>
                            <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity-foreign-actions', [
                                'candidate' => $candidate, 'for' => $for, 'allowForeign' => $allowForeign,
                                'requestUrl' => Url::to(['/cms/admin-cms-lead/work-request']),
                            ]); ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
