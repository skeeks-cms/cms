<?php
/**
 * «Привязать к сделке №…» (crm\CrmInbound): an incoming letter, call or chat
 * message of a client with several open deals; the employee links it to one
 * of his deals of that client (LeadTriageActions::attachInbound()).
 *
 * @var yii\web\View $this
 * @var string $description
 * @var skeeks\cms\models\CmsLead[] $leads
 * @var skeeks\cms\models\CmsLead[] $linked
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use yii\helpers\Html;
use yii\helpers\Url;

?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Привязать к сделке',
    'titleTag' => 'h2',
    'raised' => true,
    'responsive' => true,
]); ?>
    <p><?= Html::encode($description); ?></p>
    <?php if (!$leads) : ?>
        <p class="sx-lead-identity__note">У клиента нет ваших открытых сделок: привязать запись может ответственный за сделку.</p>
    <?php else : ?>
        <p class="sx-lead-identity__note">У клиента несколько открытых сделок. Выберите свою — запись появится в её ленте.</p>
        <?php foreach ($leads as $lead) : ?>
            <?= Html::beginForm(Url::current(), 'post', ['class' => 'sx-lead-hint__actions', 'data-pjax' => 0]); ?>
                <?= Html::hiddenInput('lead_id', (int)$lead->id); ?>
                <span class="sx-lead-hint__title">
                    <?= Html::a('Сделка №'.(int)$lead->id.' · '.Html::encode((string)$lead->name), ['/cms/admin-cms-lead/view', 'pk' => (int)$lead->id], ['data-pjax' => 0]); ?>
                </span>
                <?php if (isset($linked[$lead->id])) : ?>
                    <span class="sx-lead-hint__meta">Уже привязано</span>
                <?php else : ?>
                    <?= Html::submitButton('Привязать к сделке №'.(int)$lead->id, ['class' => 'sx-button sx-button--primary sx-button--sm']); ?>
                <?php endif; ?>
            <?= Html::endForm(); ?>
        <?php endforeach; ?>
    <?php endif; ?>
<?php BackendSurfaceWidget::end(); ?>
