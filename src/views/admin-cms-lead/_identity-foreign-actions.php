<?php
/**
 * A match with a client of another main responsible (П2а, owner's decision
 * 28.10.2026): «Работать с этим клиентом» and, for a strong match, «Это
 * другая компания». In the deal form the client is taken into the form (the
 * deal will be the employee's, level "Свои сделки"); a client «Доступ только
 * по согласованию» and the create forms of a company and a client send the
 * action at once ($requestUrl, LeadTriage::workWithClient()).
 *
 * @var array $candidate presented candidate (CrmAccess::presentCandidate())
 * @var string $for company|client
 * @var bool $allowForeign the client may be taken
 * @var string|null $requestUrl the action that sends «Работать с этим клиентом» at once
 * @var bool $sendAtOnce the create forms: the action is sent at once for any client
 * @var string|null $create the parsed line for «Это другая компания» from the dropdown (JSON)
 * @var bool $withDuplicate show «Это другая компания» (the create forms have their reason field)
 */

use skeeks\cms\crm\LeadIdentityForm;
use yii\helpers\Html;

$isCompany = $for === 'company';
$responsible = implode(', ', array_column($candidate['responsible'], 'name'));
$responsibleName = $candidate['responsible'][0]['name'] ?? '';
$byApproval = !empty($candidate['byApproval']);
$withDuplicate = $withDuplicate ?? true;
?>
<?php if ($allowForeign) : ?>
    <?php if ($byApproval || !empty($sendAtOnce)) : ?>
        <?= Html::button('Работать с этим клиентом', [
            'class' => 'sx-button sx-button--primary sx-button--sm',
            'title' => $byApproval
                ? 'С клиентом работают по согласованию: запрос уйдёт основному ответственному, сделка будет создана после одобрения.'
                : 'Сделка будет создана на вас, у вас будет доступ «Свои сделки», основному ответственному уйдёт уведомление.',
            'data-sx-identity-work' => $for,
            'data-ref' => $candidate['ref'],
            'data-url' => $requestUrl,
            // Not data-confirm: yii.js would submit the form around the button after the confirmation.
            'data-sx-confirm' => $byApproval
                ? 'С этим клиентом работают только по согласованию'.($responsibleName !== '' ? ' с сотрудником '.$responsibleName : '').'. Отправить запрос? Сделка будет создана после одобрения.'
                : 'Создать сделку на вас по этому клиенту? У вас будет доступ «Свои сделки», основному ответственному'.($responsibleName !== '' ? ' ('.$responsibleName.')' : '').' уйдёт уведомление.',
        ]); ?>
    <?php else : ?>
        <?= Html::button('Работать с этим клиентом', [
            'class' => 'sx-button sx-button--primary sx-button--sm',
            'title' => 'Сделка будет ваша, у вас будет доступ «Свои сделки», основному ответственному уйдёт уведомление.',
            'data-sx-identity-foreign' => $for,
            'data-action' => LeadIdentityForm::FOREIGN_WORK,
            'data-ref' => $candidate['ref'],
            'data-title' => $isCompany ? (string)$candidate['title'] : 'Контакт из базы',
            'data-responsible' => $responsible,
        ]); ?>
    <?php endif; ?>
<?php endif; ?>
<?php if ($withDuplicate && $candidate['strong']) : ?>
    <?= Html::button($isCompany ? 'Это другая компания' : 'Это другой человек', array_filter([
        'class' => 'sx-button sx-button--secondary sx-button--sm',
        'data-sx-identity-duplicate' => $for,
        'data-ref' => $candidate['ref'],
        'data-title' => (string)($candidate['title'] ?: 'контакт из базы'),
        'data-create' => $create ?? null,
    ], static fn($value) => $value !== null)); ?>
<?php endif; ?>
<?php if ($byApproval) : ?>
    <span class="sx-lead-hint__meta">Доступ по согласованию с основным ответственным</span>
<?php endif; ?>
