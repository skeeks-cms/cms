<?php
/**
 * Stage bar of the lead card: the path "new → stages" and the two outcomes.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 * @var array[] $steps from CmsLeadBoardService::steps()
 */

use skeeks\cms\assets\admin\LeadStageBarAsset;
use skeeks\cms\services\CmsLeadBoardService;
use yii\helpers\Html;
use yii\helpers\Url;

LeadStageBarAsset::register($this);

$renderStep = static function (array $step): string {
    $classes = ['sx-lead-stage-bar__step', 'sx-lead-stage-bar__step--'.$step['state']];
    if ($step['column'] === CmsLeadBoardService::COLUMN_SUCCESS) {
        $classes[] = 'sx-lead-stage-bar__step--success';
    } elseif ($step['column'] === CmsLeadBoardService::COLUMN_REJECTED) {
        $classes[] = 'sx-lead-stage-bar__step--danger';
    }
    $options = [
        'type' => 'button',
        'class' => implode(' ', $classes),
        'data-column' => $step['column'],
        'disabled' => !$step['allowed'],
        'aria-current' => $step['state'] === 'current' ? 'step' : null,
    ];
    if ($step['confirm']) {
        $options['data-confirm-text'] = $step['confirm'];
    }
    if ($step['stage'] && $step['stage']->color) {
        $options['style'] = '--sx-lead-stage-color: '.$step['stage']->color;
    }

    $marker = '';
    if ($step['state'] === 'current') {
        $marker = Html::tag('span', '', ['class' => 'sx-lead-stage-bar__pulse', 'aria-hidden' => 'true']);
    } elseif ($step['state'] === 'done') {
        $marker = Html::tag('i', '', ['class' => 'fas fa-check sx-lead-stage-bar__check', 'aria-hidden' => 'true']);
    }

    return Html::tag('li', Html::button($marker.Html::encode($step['title']), $options));
};

$path = array_filter($steps, static fn(array $step) => !in_array($step['column'], [CmsLeadBoardService::COLUMN_SUCCESS, CmsLeadBoardService::COLUMN_REJECTED], true));
$outcomes = array_diff_key($steps, $path);
?>
<nav class="sx-lead-stage-bar" aria-label="Этапы лида" data-sx-lead-stage-bar
     data-move-url="<?= Html::encode(Url::to(['/cms/admin-cms-lead/kanban-move'])); ?>"
     data-pk="<?= (int)$model->id; ?>">
    <ol class="sx-lead-stage-bar__path"><?= implode('', array_map($renderStep, $path)); ?></ol>
    <ol class="sx-lead-stage-bar__outcomes"><?= implode('', array_map($renderStep, $outcomes)); ?></ol>
</nav>
