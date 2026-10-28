<?php
/**
 * Candidates of CrmMatcher as the employee may see them (CrmAccess::presentCandidate()).
 * Buttons only fill the identity form (lead-identity.js); nothing is linked here.
 *
 * @var array[] $candidates
 * @var bool $live loaded while typing (no empty state)
 */

use skeeks\cms\crm\CrmMatcher;
use yii\helpers\Html;
use yii\helpers\Url;

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
$kinds = [
    CrmMatcher::TYPE_COMPANY => 'Компания',
    CrmMatcher::TYPE_CLIENT => 'Контакт',
    CrmMatcher::TYPE_LEAD => 'Открытая сделка',
];
$live = $live ?? false;
?>
<?php if (!$candidates) : ?>
    <?php if (!$live) : ?>
        <p class="sx-lead-identity__hints-empty">Совпадений в базе нет — это новый клиент.</p>
    <?php endif; ?>
<?php else : ?>
    <div class="sx-lead-identity__hints-title">Найдено в базе</div>
    <ul class="sx-lead-hints">
        <?php foreach ($candidates as $candidate) : ?>
            <?php
            $type = $candidate['type'];
            $responsible = implode(', ', array_column($candidate['responsible'], 'name'));
            $title = $candidate['title'];
            if (!$candidate['visible'] && $type !== CrmMatcher::TYPE_LEAD) {
                $title = $type === CrmMatcher::TYPE_COMPANY ? 'Такая компания уже есть: «'.$title.'»' : 'Такой контакт уже есть в базе';
            }
            ?>
            <li class="sx-lead-hint<?= $candidate['strong'] ? ' is-strong' : ''; ?><?= $candidate['visible'] ? '' : ' is-hidden'; ?>">
                <div class="sx-lead-hint__main">
                    <span class="sx-lead-hint__kind"><?= Html::encode($kinds[$type] ?? $type); ?><?= $candidate['strong'] ? '' : ' · возможно'; ?></span>
                    <span class="sx-lead-hint__title">
                        <?php if ($candidate['visible'] && $type === CrmMatcher::TYPE_LEAD) : ?>
                            <?= Html::a(Html::encode($title).' №'.(int)$candidate['id'], Url::to(['/cms/admin-cms-lead/view', 'pk' => $candidate['id']]), ['target' => '_blank', 'data-pjax' => 0]); ?>
                        <?php elseif ($candidate['visible'] && $type === CrmMatcher::TYPE_COMPANY) : ?>
                            <?= Html::a(Html::encode($title), Url::to(['/cms/admin-cms-company/view', 'pk' => $candidate['id']]), ['target' => '_blank', 'data-pjax' => 0]); ?>
                        <?php elseif ($candidate['visible'] && $type === CrmMatcher::TYPE_CLIENT) : ?>
                            <?= Html::a(Html::encode($title), Url::to(['/cms/admin-user/view', 'pk' => $candidate['id']]), ['target' => '_blank', 'data-pjax' => 0]); ?>
                        <?php else : ?>
                            <?= Html::encode($title); ?>
                        <?php endif; ?>
                    </span>
                    <span class="sx-lead-hint__meta">
                        Совпал <?= Html::encode(implode(', ', array_map($reasonText, $candidate['reasons']))); ?>
                    </span>
                    <span class="sx-lead-hint__meta">
                        <?= $responsible !== '' ? 'Ответственный: '.Html::encode($responsible) : 'Ответственного нет'; ?>
                        <?php if ($candidate['hasOpenLeads'] && $type !== CrmMatcher::TYPE_LEAD) : ?> · есть открытые сделки<?php endif; ?>
                    </span>
                    <?php if (!empty($candidate['companies'])) : ?>
                        <span class="sx-lead-hint__meta">Компании: <?= Html::encode(implode(', ', array_column($candidate['companies'], 'name'))); ?></span>
                    <?php endif; ?>
                </div>
                <div class="sx-lead-hint__actions">
                    <?php if ($type !== CrmMatcher::TYPE_LEAD) : ?>
                        <?php if ($candidate['visible']) : ?>
                            <?= Html::button('Выбрать', [
                                'class' => 'sx-button sx-button--secondary sx-button--sm',
                                'data-sx-identity-pick' => $type,
                                'data-id' => (int)$candidate['id'],
                                'data-title' => (string)$candidate['title'],
                                'data-companies' => \yii\helpers\Json::encode($candidate['companies'] ?? []),
                            ]); ?>
                        <?php elseif ($candidate['strong']) : ?>
                            <?= Html::button('Это он', [
                                'class' => 'sx-button sx-button--secondary sx-button--sm',
                                'title' => 'Сделка уйдёт ответственному клиента, можно запросить её себе',
                                'data-sx-identity-foreign' => $type,
                                'data-ref' => $candidate['ref'],
                                'data-title' => $type === CrmMatcher::TYPE_COMPANY ? (string)$candidate['title'] : 'Контакт из базы',
                                'data-responsible' => $responsible,
                            ]); ?>
                        <?php endif; ?>
                        <?= Html::button($type === CrmMatcher::TYPE_COMPANY ? 'Это другая компания' : 'Это другой человек', [
                            'class' => 'sx-button sx-button--secondary sx-button--sm sx-lead-hint__alt',
                            'data-sx-identity-duplicate' => $type,
                            'data-ref' => $candidate['ref'],
                            'data-title' => (string)($candidate['title'] ?: 'контакт из базы'),
                        ]); ?>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
