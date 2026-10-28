<?php
/**
 * «Передать клиента» (П2а): the new main responsible; open deals and tasks go
 * to him except those kept with their executors. ClientTransferActions.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 * @var string $recordCode
 * @var array[] $responsible current links [id, name]
 * @var array $work leads (CmsLead[]), tasks (CmsTask[])
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
?>
<?= Html::beginForm(Url::current(), 'post', ['data-sx-lead-ajax-form' => true]); ?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Передать клиента',
        'titleTag' => 'h2',
        'hint' => 'Новый сотрудник станет основным ответственным. Прежний основной останется с доступом «Свои сделки и данные компании» — его можно убрать в «Доступ к клиенту». Закрытые сделки и история не меняются.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <div class="sx-lead-identity__part">
            <p class="sx-lead-identity__note">Сейчас работают с клиентом: <?= Html::encode(implode(', ', array_column($responsible, 'name')) ?: '—'); ?></p>
            <?= AjaxSelectModel::widget([
                'id' => 'sx-crm-transfer-executor',
                'name' => 'executor_id',
                'modelClass' => CmsUser::class,
                'modelShowAttribute' => 'shortDisplayName',
                'searchQuery' => static function ($word = '') {
                    $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1]);
                    return $word ? $query->search($word) : $query;
                },
                'options' => ['placeholder' => 'Новый ответственный'],
            ]); ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if ($work['leads'] || $work['tasks']) : ?>
        <?php BackendSurfaceWidget::begin([
            'title' => 'Открытые сделки и задачи',
            'titleTag' => 'h2',
            'hint' => 'Перейдут новому ответственному. Отметьте те, что остаются у прежнего исполнителя.',
            'raised' => true,
            'responsive' => true,
        ]); ?>
            <ul class="sx-lead-triage__repeats">
                <?php foreach ($work['leads'] as $lead) : ?>
                    <li class="sx-lead-triage__repeat">
                        <span>Сделка №<?= (int)$lead->id; ?> «<?= Html::encode((string)$lead->name); ?>»
                            <span class="sx-lead-hint__meta">— <?= Html::encode($lead->executor ? (string)$lead->executor->shortDisplayName : 'без ответственного'); ?></span></span>
                        <label class="sx-lead-identity__choice"><?= Html::checkbox('keep_leads[]', false, ['value' => (int)$lead->id]); ?> оставить</label>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($work['tasks'] as $task) : ?>
                    <li class="sx-lead-triage__repeat">
                        <span>Задача №<?= (int)$task->id; ?> «<?= Html::encode((string)$task->name); ?>»
                            <span class="sx-lead-hint__meta">— <?= Html::encode($task->executor ? (string)$task->executor->shortDisplayName : '—'); ?></span></span>
                        <label class="sx-lead-identity__choice"><?= Html::checkbox('keep_tasks[]', false, ['value' => (int)$task->id]); ?> оставить</label>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>

    <div class="sx-lead-triage__actions">
        <?= Html::submitButton('Передать', [
            'class' => 'sx-button sx-button--primary',
            'data-confirm' => 'Передать клиента? Новый сотрудник станет основным ответственным.',
        ]); ?>
    </div>
</div>
<?= Html::endForm(); ?>
