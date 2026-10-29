<?php
/**
 * Matches under the create form of a company or a client (П2а, CrmDuplicateCheckActions):
 * own records open, a client of another main responsible offers «Работать с
 * этим клиентом»; the parsed line is handed to the script that splits it into the
 * fields (lead-identity.js). The deal form has its own dropdown and check
 * (_identity-dropdown, _identity-check).
 *
 * @var array[] $candidates presented candidates (CrmAccess::presentCandidate())
 * @var string $for company|client
 * @var array $parsed CrmContactParser::parse() of the typed line
 * @var string|null $requestUrl the duplicate-request action for a client of another responsible
 */

use skeeks\cms\crm\CrmMatcher;
use skeeks\cms\crm\CrmProtection;
use yii\helpers\Html;
use yii\helpers\Json;

$reasonText = static function (array $reason): string {
    $labels = [
        CrmMatcher::BY_EMAIL => 'email',
        CrmMatcher::BY_PHONE => 'телефон',
        CrmMatcher::BY_INN => 'ИНН',
        CrmMatcher::BY_DOMAIN => 'домен',
        CrmMatcher::BY_NAME => 'название',
    ];

    return ($labels[$reason['by']] ?? $reason['by']).' '.$reason['value'];
};
$isCompany = $for === CrmMatcher::TYPE_COMPANY;
// A contact search: a typed text is the person's name.
$createTitle = $isCompany ? trim((string)$parsed['name']) : trim((string)($parsed['name'] ?: $parsed['person']));
// A record with the same email, phone or INN already exists: saved only with «Почему это другая компания».
$hasStrong = (bool)array_filter($candidates, static fn($candidate) => $candidate['strong']);
?>
<ul class="sx-lead-results" role="listbox">
    <?php if ($candidates) : ?>
        <li class="sx-lead-results__head"<?= $hasStrong ? ' data-sx-dupcheck-strong' : ''; ?>><?= $hasStrong ? 'Такая запись уже есть в базе — проверьте, прежде чем создавать' : 'Похожие записи в базе'; ?></li>
    <?php endif; ?>
    <?php foreach ($candidates as $candidate) : ?>
        <?php
        $responsible = implode(', ', array_column($candidate['responsible'], 'name'));
        $title = (string)$candidate['title'];
        if (!$candidate['visible']) {
            $title = $isCompany ? 'Такая компания уже есть: «'.$title.'»' : 'Такой контакт уже есть в базе';
        }
        ?>
        <li class="sx-lead-results__item<?= $candidate['visible'] ? '' : ' is-hidden'; ?>">
            <div class="sx-lead-hint__main">
                <span class="sx-lead-hint__title"><?= Html::encode($title); ?><?= CrmProtection::shieldIcon((string)($candidate['protection'] ?? ''), (string)($candidate['protectionText'] ?? '')); ?></span>
                <span class="sx-lead-hint__meta">
                    <?= $candidate['strong'] ? 'Совпал ' : 'Похоже: '; ?><?= Html::encode(implode(', ', array_map($reasonText, $candidate['reasons']))); ?>
                </span>
                <span class="sx-lead-hint__meta">
                    <?= $responsible !== '' ? 'Ответственный: '.Html::encode($responsible) : 'Ответственного нет'; ?>
                    <?php if ($candidate['hasOpenLeads']) : ?> · есть открытые сделки<?php endif; ?>
                </span>
            </div>
            <div class="sx-lead-hint__actions">
                <?php if ($candidate['visible']) : ?>
                    <?= Html::a('Открыть', [$isCompany ? '/cms/admin-cms-company/view' : '/cms/admin-user/view', 'pk' => (int)$candidate['id']], [
                        'class' => 'sx-button sx-button--secondary sx-button--sm',
                        'target' => '_blank',
                        'data-pjax' => 0,
                    ]); ?>
                <?php elseif (!empty($requestUrl) && in_array($candidate['type'], [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT], true)) : ?>
                    <?php // A client of another main responsible: «Работать с этим клиентом» at once (the reason field is in the form). ?>
                    <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity-foreign-actions', [
                        'candidate' => $candidate, 'for' => $for, 'allowForeign' => true, 'sendAtOnce' => true,
                        'requestUrl' => $requestUrl, 'withDuplicate' => false,
                    ]); ?>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach; ?>
    <?php // The parsed line: a pasted name is split into the form fields by the script. ?>
    <li hidden data-sx-dupcheck-parsed data-create="<?= Html::encode(Json::encode($parsed + ['title' => $createTitle])); ?>"></li>
</ul>
