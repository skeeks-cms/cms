<?php
/**
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 */

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\controllers\AdminCmsLeadController;
use yii\helpers\Html;

$classes = ['sx-lead-kanban__card'];
if (!$model->isTerminal) {
    if ($model->isNextStepOverdue) {
        $classes[] = 'sx-lead-kanban__card--overdue';
    } elseif ($model->isNextStepMissing) {
        $classes[] = 'sx-lead-kanban__card--missing';
    }
}

$meta = [$model->sourceNameAsText, Yii::$app->formatter->asRelativeTime($model->created_at)];
if ($model->executor) {
    $meta[] = $model->executor->shortDisplayName;
}
?>
<div class="<?= implode(' ', $classes); ?>" data-sx-kanban-card data-pk="<?= (int)$model->id; ?>">
    <?= BackendEntityLink::widget([
        'controllerId' => '/cms/admin-cms-lead',
        'action' => 'view',
        'modelId' => $model->id,
        'label' => $model->displayName,
        'options' => ['class' => 'sx-lead-kanban__title'],
    ]); ?>
    <?php
    // As in amoCRM: who the deal is with. A company name is always shown (the brief view);
    // a person only when the viewer may see the client (П2а masks private persons).
    $who = [];
    if ($model->client && \skeeks\cms\crm\CrmAccess::instance()->canSeeClient((int)Yii::$app->user->id, (int)$model->client->id)) {
        $who[] = (string)$model->client->shortDisplayName;
    }
    if ($model->company) {
        $who[] = (string)$model->company->name;
    }
    ?>
    <?php if ($who) : ?>
        <div class="sx-lead-kanban__client"><?= Html::encode(implode(', ', $who)); ?></div>
    <?php endif; ?>
    <div class="sx-lead-kanban__meta"><?= Html::encode(implode(' · ', array_filter($meta))); ?></div>
    <?php if ($model->isTerminal) : ?>
        <?php $result = $model->status === \skeeks\cms\models\CmsLead::STATUS_REJECTED ? $model->reject_reason : $model->result_comment; ?>
        <?php if ($model->status === \skeeks\cms\models\CmsLead::STATUS_REJECTED && $model->rejectReasonItem) : ?>
            <span class="sx-status"><?= Html::encode($model->rejectReasonItem->name); ?></span>
            <?php if ($result === $model->rejectReasonItem->name) { $result = null; } ?>
        <?php elseif ($model->status === \skeeks\cms\models\CmsLead::STATUS_SUCCESS && $model->dealAmountAsText !== '') : ?>
            <?php // A successful deal shows only «Сумма сделки»; «Сумма» as a hint when it was different. ?>
            <span class="sx-status" title="<?= Html::encode(trim('Сумма сделки '.$model->expectedAmountNote)); ?>"><?= Html::encode($model->dealAmountAsText); ?></span>
        <?php endif; ?>
        <?php if ($result) : ?>
            <div class="sx-lead-kanban__meta"><?= Html::encode(\yii\helpers\StringHelper::truncate($result, 120)); ?></div>
        <?php endif; ?>
    <?php else : ?>
        <?php if ($model->amountAsText !== '') : ?>
            <?php // An open deal shows only «Сумма». ?>
            <span class="sx-status" title="Сумма"><?= Html::encode($model->amountAsText); ?></span>
        <?php endif; ?>
        <?= AdminCmsLeadController::renderNextStepCell($model); ?>
    <?php endif; ?>
</div>
