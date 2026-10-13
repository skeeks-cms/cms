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
    <div class="sx-lead-kanban__meta"><?= Html::encode(implode(' · ', array_filter($meta))); ?></div>
    <?php if ($model->isTerminal) : ?>
        <?php $result = $model->status === \skeeks\cms\models\CmsLead::STATUS_REJECTED ? $model->reject_reason : $model->result_comment; ?>
        <?php if ($result) : ?>
            <div class="sx-lead-kanban__meta"><?= Html::encode(\yii\helpers\StringHelper::truncate($result, 120)); ?></div>
        <?php endif; ?>
    <?php else : ?>
        <?= AdminCmsLeadController::renderNextStepCell($model); ?>
    <?php endif; ?>
</div>
