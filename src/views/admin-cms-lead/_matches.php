<?php
/**
 * «Найдены совпадения в CRM» of the deal card: one line per match, as the
 * repeats list of amoCRM and Bitrix24 — the kind, the name, №, the main
 * responsible, what matched, «возможный дубль» (an open cms_crm_duplicate_mark
 * between the found records) and «Это он» / «Привязать». Three lines, the rest
 * under «ещё N». The shared rows of the CRM matches (lead-identity.css,
 * registered by the card header); no styles of its own.
 */

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmDuplicateMark;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use yii\helpers\Html;

/** @var CmsLead $model */
/** @var array $matches */
/** @var bool $canLink */
/** @var bool $canTake an unsorted deal the employee sorts: «Это он» takes it into work with the record (п. 13б) */

$canTake = $canTake ?? false;
$access = \skeeks\cms\crm\CrmAccess::instance();

// «Это он» (LeadTriageActions::triagePlain()) or «Привязать» (link-identity) of a deal in work.
$button = static function (?int $companyId, ?int $clientId, string $title) use ($model, $canTake): string {
    return Html::beginForm([$canTake ? '/cms/admin-cms-lead/triage' : '/cms/admin-cms-lead/link-identity', 'pk' => $model->id], 'post')
        .Html::hiddenInput('company_id', $companyId)
        .Html::hiddenInput('client_id', $clientId)
        .Html::submitButton($canTake ? 'Это он' : 'Привязать', [
            'class' => 'sx-button sx-button--'.($canTake ? 'primary' : 'secondary').' sx-button--sm',
            'data-confirm' => $canTake
                ? 'Взять сделку в работу с клиентом «'.$title.'»? Ответственный — вы.'
                : 'Привязать «'.$title.'» к сделке?',
        ])
        .Html::endForm();
};
$mainName = static function (string $code, int $id) use ($access): string {
    $mainId = $access->mainResponsibleId($code, $id);
    $main = $mainId ? CmsUser::findOne($mainId) : null;

    return $main ? (string)$main->shortDisplayName : '';
};

// Open «возможный дубль» marks between the found records (CrmDuplicateGuard, LeadTriage «Это другая компания»).
$found = [];
foreach ($matches['companies'] as $match) {
    $found[] = ['company', (int)$match['model']->id];
}
foreach ($matches['clients'] as $match) {
    $found[] = ['client', (int)$match['model']->id];
}
$duplicates = [];
if (count($found) > 1 && \skeeks\cms\crm\CrmDuplicateGuard::ready()) {
    $keys = array_map(static fn($record) => $record[0].':'.$record[1], $found);
    foreach (CmsCrmDuplicateMark::find()->andWhere(['status' => CmsCrmDuplicateMark::STATUS_OPEN])->andWhere(['or',
        ['record_id' => array_column($found, 1)], ['other_id' => array_column($found, 1)],
    ])->all() as $mark) {
        $a = $mark->record_code.':'.(int)$mark->record_id;
        $b = $mark->other_code.':'.(int)$mark->other_id;
        if (in_array($a, $keys, true) && in_array($b, $keys, true)) {
            $duplicates[$a] = $duplicates[$b] = (string)$mark->reason;
        }
    }
}

$rows = [];
foreach ($matches['companies'] as $match) {
    /** @var CmsCompany $company */
    $company = $match['model'];
    $rows[] = [
        'code' => 'company', 'id' => (int)$company->id, 'icon' => 'fas fa-building', 'kind' => 'Компания',
        'controller' => '/cms/admin-cms-company', 'title' => (string)$company->asText, 'reasons' => $match['reasons'],
        'extra' => '', 'linked' => (int)$model->cms_company_id === (int)$company->id,
        'button' => ($canTake || ($canLink && !$model->cms_company_id)) ? $button((int)$company->id, null, (string)$company->asText) : '',
    ];
}
foreach ($matches['clients'] as $match) {
    /** @var CmsUser $client */
    $client = $match['model'];
    $companies = $match['companies'] ?? [];
    // A person of one company is taken with it.
    $companyId = count($companies) === 1 && !$model->cms_company_id ? (int)$companies[0]->id : null;
    $rows[] = [
        'code' => 'client', 'id' => (int)$client->id, 'icon' => 'fas fa-user', 'kind' => 'Контакт',
        'controller' => '/cms/admin-user', 'title' => (string)$client->shortDisplayName, 'reasons' => $match['reasons'],
        'extra' => $companies ? 'компании: '.implode(', ', array_map(static fn($c) => (string)$c->asText, $companies)) : '',
        'linked' => (int)$model->cms_user_id === (int)$client->id,
        'button' => ($canTake || ($canLink && !$model->cms_user_id)) ? $button($companyId, (int)$client->id, (string)$client->shortDisplayName) : '',
    ];
}

$renderRow = static function (array $row) use ($mainName, $duplicates): string {
    $main = $mainName($row['code'], $row['id']);
    $meta = array_filter([
        '№'.$row['id'],
        $main !== '' ? 'ответственный '.$main : 'без ответственного',
        mb_strtolower(implode(', ', $row['reasons']), 'UTF-8'),
        $row['extra'],
    ]);
    $duplicate = $duplicates[$row['code'].':'.$row['id']] ?? null;

    return Html::tag('li',
        Html::tag('span',
            Html::tag('i', '', ['class' => $row['icon'].' sx-lead-hint__kind', 'title' => $row['kind'], 'aria-label' => $row['kind']])
            .BackendEntityLink::widget([
                'controllerId' => $row['controller'],
                'modelId' => $row['id'],
                'label' => $row['title'],
                'options' => ['class' => 'sx-lead-hint__title'],
            ])
            .Html::tag('span', Html::encode(implode(' · ', $meta)), ['class' => 'sx-lead-hint__meta'])
            .($duplicate !== null ? Html::tag('span', 'возможный дубль', [
                'class' => 'sx-status sx-status--warning',
                'title' => 'Отмечены как возможный повтор'.($duplicate !== '' ? ': '.$duplicate : '').'. Объединить их может администратор.',
            ]) : '')
            .($row['linked'] ? Html::tag('span', 'уже в сделке', ['class' => 'sx-status sx-status--success']) : ''),
            ['class' => 'sx-lead-triage__actions']
        )
        .($row['button'] !== '' && !$row['linked'] ? Html::tag('span', $row['button'], ['class' => 'sx-lead-hint__actions']) : ''),
        ['class' => 'sx-lead-hint is-strong']
    );
};

BackendSurfaceWidget::begin([
    'id' => 'cms-lead-matches-surface',
    'title' => 'Найдены совпадения в CRM',
    'actions' => Html::tag('span', $canTake ? '«Это он» — взять в работу с этим клиентом' : 'Проверьте перед созданием новой записи', [
        'class' => 'sx-collection-cell__secondary',
    ]),
    'titleTag' => 'h2',
    'raised' => true,
    'responsive' => true,
    'options' => ['class' => 'sx-lead-matches-surface'],
]);
?>
    <ul class="sx-lead-hints">
        <?php foreach (array_slice($rows, 0, 3) as $row) : ?>
            <?= $renderRow($row); ?>
        <?php endforeach; ?>
    </ul>
    <?php if (count($rows) > 3) : ?>
        <details>
            <summary class="sx-collection-cell__secondary">ещё <?= count($rows) - 3; ?></summary>
            <ul class="sx-lead-hints">
                <?php foreach (array_slice($rows, 3) as $row) : ?>
                    <?= $renderRow($row); ?>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php endif; ?>
<?php BackendSurfaceWidget::end(); ?>
