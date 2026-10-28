<?php
/**
 * «Перенести в другую воронку»: the pipeline and its stage (LeadPipelines::move()).
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 * @var skeeks\cms\models\CmsLeadPipeline[] $pipelines
 * @var string|null $error
 */

use skeeks\cms\models\CmsLead;
use yii\helpers\Html;
use yii\helpers\Json;

$current = $model->getEffectivePipeline();
$stages = [];
foreach ($pipelines as $pipeline) {
    foreach ($pipeline->stages as $stage) {
        $stages[(int)$pipeline->id][] = ['id' => (int)$stage->id, 'name' => (string)$stage->name];
    }
}
$targets = array_filter($pipelines, static fn($pipeline) => !$current || (int)$pipeline->id !== (int)$current->id);
$first = reset($targets);
$isInWork = $model->status === CmsLead::STATUS_IN_WORK;
$stagesJson = Json::htmlEncode($stages);
$this->registerJs(<<<JS
(function () {
    var stages = {$stagesJson};
    var fill = function () {
        var select = $('#sx-move-stage');
        var list = stages[$('#sx-move-pipeline').val()] || [];
        select.empty().append($('<option>').val('').text('Первый этап воронки'));
        list.forEach(function (stage) { select.append($('<option>').val(stage.id).text(stage.name)); });
    };
    $('#sx-move-pipeline').on('change', fill);
    fill();
})();
JS
);
?>
<div class="sx-surface sx-surface--padded">
    <?php if ($error) : ?>
        <div class="sx-status sx-status--danger" role="alert"><?= Html::encode($error); ?></div>
    <?php endif; ?>
    <?php if (!$targets) : ?>
        <p>Других воронок нет. Их можно добавить в настройках «Воронки и этапы».</p>
    <?php else : ?>
        <?= Html::beginForm('', 'post'); ?>
            <p>Сейчас: «<?= Html::encode($current ? $current->name : ''); ?>». История переходов сохранится.</p>
            <div class="form-group">
                <label for="sx-move-pipeline">Воронка</label>
                <?= Html::dropDownList('pipeline_id', $first ? (int)$first->id : null,
                    \yii\helpers\ArrayHelper::map($targets, 'id', 'name'), ['id' => 'sx-move-pipeline', 'class' => 'form-control']); ?>
            </div>
            <?php if ($isInWork) : ?>
                <div class="form-group">
                    <label for="sx-move-stage">Этап</label>
                    <?= Html::dropDownList('stage_id', null, [], ['id' => 'sx-move-stage', 'class' => 'form-control']); ?>
                </div>
            <?php else : ?>
                <p class="sx-surface__hint">Сделка ещё не разобрана: в новой воронке она тоже будет в «Неразобранном».</p>
            <?php endif; ?>
            <?= Html::submitButton('Перенести', ['class' => 'sx-button sx-button--primary']); ?>
        <?= Html::endForm(); ?>
    <?php endif; ?>
</div>
