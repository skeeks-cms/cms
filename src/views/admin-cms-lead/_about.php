<?php
/**
 * «О сделке» at the top of the left column (Bitrix24 «О сделке», amoCRM main
 * fields): pipeline, stage, time on it, amount, responsible, source, dates
 * of opening and closing, the result.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 * @var bool $canWork
 */

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\LeadPipelines;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use yii\helpers\Html;
use yii\helpers\Json;

$formatter = Yii::$app->formatter;
$pipeline = $model->getEffectivePipeline();
$rows = [];
if ($pipeline && count(CmsLeadPipeline::forSite((int)$model->cms_site_id)) > 1) {
    $value = Html::encode($pipeline->name);
    if (!$model->isTerminal && ($canWork || $model->status === CmsLead::STATUS_NEW)) {
        $moveUrl = (string)BackendUrlHelper::createByParams(['/cms/admin-cms-lead/move-pipeline', 'pk' => $model->id])
            ->enableEmptyLayout()->enableNoActions()->url;
        $value .= ' '.Html::a('перенести', $moveUrl, [
            'class' => 'sx-lead-about__action',
            'data-pjax' => '0',
            'onclick' => 'new sx.classes.backend.widgets.Action('.Json::encode(['isOpenNewWindow' => true, 'url' => $moveUrl, 'size' => 'small']).').go(); return false;',
        ]);
    }
    $rows['Воронка'] = $value;
}
$place = $model->staffStatusName;
if ($model->status === CmsLead::STATUS_IN_WORK && $model->stage) {
    $place = $model->stage->name;
}
$onStage = LeadPipelines::instance()->timeOnStageText($model);
$rows['Этап'] = Html::encode($place).($onStage !== '' ? Html::tag('span', ' · на этапе '.$onStage, ['class' => 'sx-lead-about__muted']) : '');
[$amountLabel, $amountText] = $model->shownAmount;
$rows[$amountLabel] = $amountText !== '' ? Html::encode($amountText) : Html::tag('span', 'не указана', ['class' => 'sx-lead-about__muted']);
if ($model->expectedAmountNote !== '') {
    $rows[$amountLabel] .= Html::tag('span', ' · '.$model->expectedAmountNote, ['class' => 'sx-lead-about__muted']);
}
$rows['Ответственный'] = $model->executor ? Html::encode($model->executor->shortDisplayName) : Html::tag('span', 'не назначен', ['class' => 'sx-lead-about__muted']);
$rows['Источник'] = Html::encode($model->sourceNameAsText);
$rows['Открыта'] = $formatter->asDatetime($model->created_at, 'd MMMM yyyy, HH:mm');
if ($model->isTerminal && $model->processed_at) {
    $rows['Закрыта'] = $formatter->asDatetime($model->processed_at, 'd MMMM yyyy, HH:mm');
}
if ($model->status === CmsLead::STATUS_REJECTED && !$model->triageResult) {
    $reason = $model->rejectReasonItem ? $model->rejectReasonItem->name : (string)$model->reject_reason;
    if ($reason !== '') {
        $rows['Причина отказа'] = Html::encode($reason);
    }
}

$this->registerCss(<<<CSS
.sx-lead-about {
    display: grid;
    grid-template-columns: minmax(7rem, auto) 1fr;
    gap: 0.5rem 0.75rem;
    margin: 0;
}
.sx-lead-about dt {
    color: var(--sx-color-text-muted);
    font-weight: 400;
}
.sx-lead-about dd {
    margin: 0;
    min-width: 0;
    overflow-wrap: anywhere;
}
.sx-lead-about__muted {
    color: var(--sx-color-text-muted);
}
.sx-lead-about__action {
    font-size: 0.8125rem;
}
CSS
);
?>
<?php BackendSurfaceWidget::begin(['title' => 'О сделке', 'titleTag' => 'h2', 'raised' => true, 'responsive' => true]); ?>
    <dl class="sx-lead-about">
        <?php foreach ($rows as $label => $value) : ?>
            <dt><?= Html::encode($label); ?></dt>
            <dd><?= $value; ?></dd>
        <?php endforeach; ?>
    </dl>
<?php BackendSurfaceWidget::end(); ?>
