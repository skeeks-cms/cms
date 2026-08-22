<?php

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\admin\CmsUserViewWidget;
use yii\helpers\Html;

/** @var CmsLead $model */
/** @var array $matches */
/** @var bool $canLink */

$linkButton = static function (?int $companyId, ?int $clientId, string $label, string $confirm) use ($model): string {
    return Html::beginForm(['/cms/admin-cms-lead/link-identity', 'pk' => $model->id], 'post', [
        'class' => 'sx-lead-match__form',
    ])
        .Html::hiddenInput('company_id', $companyId)
        .Html::hiddenInput('client_id', $clientId)
        .Html::submitButton($label, [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-confirm' => $confirm,
        ])
        .Html::endForm();
};

BackendSurfaceWidget::begin([
    'id' => 'cms-lead-matches-surface',
    'title' => 'Найдены совпадения в CRM',
    'hint' => 'Проверьте найденные записи перед созданием новых.',
    'titleTag' => 'h2',
    'raised' => true,
    'responsive' => true,
    'options' => ['class' => 'sx-lead-matches-surface'],
]);
?>
    <div class="sx-lead-matches__list">
        <?php foreach ($matches['clients'] as $match) : ?>
            <?php /** @var CmsUser $client */ $client = $match['model']; ?>
            <article class="sx-lead-match">
                <div class="sx-lead-match__main">
                    <span class="sx-lead-match__kind">Клиент</span>
                    <?= CmsUserViewWidget::widget(['cmsUser' => $client, 'isSmall' => true]); ?>
                    <span class="sx-lead-match__reason"><?= Html::encode(implode(' · ', $match['reasons'])); ?></span>
                </div>
                <?php if ($canLink && !$model->cms_user_id) : ?>
                    <?= $linkButton(null, (int)$client->id, 'Привязать клиента', 'Привязать этого клиента к лиду?'); ?>
                <?php endif; ?>

                <?php if ($match['companies']) : ?>
                    <div class="sx-lead-match__relations">
                        <span class="sx-lead-match__relations-title">Связан с компаниями</span>
                        <?php foreach ($match['companies'] as $company) : ?>
                            <?php /** @var CmsCompany $company */ ?>
                            <div class="sx-lead-match__relation">
                                <?= BackendEntityLink::widget([
                                    'controllerId' => '/cms/admin-cms-company',
                                    'modelId' => $company->id,
                                    'label' => $company->asText,
                                ]); ?>
                                <?php if ((int)$model->cms_company_id === (int)$company->id) : ?>
                                    <span class="sx-lead-match__reason">Уже привязана к лиду</span>
                                <?php elseif ($canLink && !$model->cms_company_id) : ?>
                                    <?= $linkButton(
                                        (int)$company->id,
                                        (int)$client->id,
                                        $model->cms_user_id ? 'Привязать компанию' : 'Привязать оба',
                                        'Привязать к лиду клиента и связанную с ним компанию?'
                                    ); ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php foreach ($matches['companies'] as $match) : ?>
            <?php /** @var CmsCompany $company */ $company = $match['model']; ?>
            <article class="sx-lead-match">
                <div class="sx-lead-match__main">
                    <span class="sx-lead-match__kind">Компания</span>
                    <?= BackendEntityLink::widget([
                        'controllerId' => '/cms/admin-cms-company',
                        'modelId' => $company->id,
                        'label' => $company->asText,
                    ]); ?>
                    <span class="sx-lead-match__reason"><?= Html::encode(implode(' · ', $match['reasons'])); ?></span>
                </div>
                <?php if ($canLink && !$model->cms_company_id) : ?>
                    <?= $linkButton((int)$company->id, null, 'Привязать компанию', 'Привязать эту компанию к лиду?'); ?>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$canLink) : ?>
            <p class="sx-lead-matches__notice">Возьмите лид в работу, чтобы привязать найденную запись.</p>
        <?php endif; ?>
    </div>
<?php BackendSurfaceWidget::end(); ?>
