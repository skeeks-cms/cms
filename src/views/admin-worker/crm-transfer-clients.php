<?php
/**
 * «Передать всех клиентов» of an employee (owner's decision 29.10.2026, п. 13а)
 * in the standard backend window: who gets them (his head by default), the
 * open deals and tasks that move with them («Оставить» keeps one with him),
 * «Сотрудник увольняется» — he leaves the clients and is deactivated.
 * AdminWorkerController::crmTransferClients(), crm\CrmClientTransfer::transferAllOf().
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsUser $model
 * @var array $work clients, leads, tasks (CrmClientTransfer::workOf())
 * @var int|null $defaultUserId
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$workerId = (int)$model->id;
?>
<?= Html::beginForm(Url::current(), 'post', ['data-sx-lead-ajax-form' => true, 'class' => 'sx-crm-access-add']); ?>
    <p>Клиентов, у которых <?= Html::encode((string)$model->shortDisplayName); ?> основной ответственный: <strong><?= count($work['clients']); ?></strong>.</p>
    <?php if (!$work['clients']) : ?>
        <p class="sx-lead-identity__note">Передавать нечего.</p>
    <?php endif; ?>
    <div class="form-group">
        <label for="sx-crm-transfer-user">Кому передать</label>
        <?= AjaxSelectModel::widget([
            'id' => 'sx-crm-transfer-user',
            'name' => 'user_id',
            'value' => $defaultUserId,
            'modelClass' => CmsUser::class,
            'modelShowAttribute' => 'shortDisplayName',
            'searchQuery' => static function ($word = '') use ($workerId) {
                $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1])
                    ->andWhere(['<>', CmsUser::tableName().'.id', $workerId]);

                return $word ? $query->search($word) : $query;
            },
            'options' => ['placeholder' => 'Сотрудник или руководитель'],
        ]); ?>
        <p class="sx-lead-identity__note">По умолчанию — руководитель сотрудника. Новый сотрудник станет основным ответственным у всех клиентов.</p>
    </div>
    <?php if ($work['leads']) : ?>
        <fieldset>
            <legend>Открытые сделки перейдут вместе с клиентами</legend>
            <?php foreach ($work['leads'] as $lead) : ?>
                <label class="d-block"><?= Html::checkbox('keep_leads[]', false, ['value' => (int)$lead->id]); ?>
                    Оставить у сотрудника: сделка №<?= (int)$lead->id; ?> · <?= Html::encode((string)$lead->name); ?></label>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>
    <?php if ($work['tasks']) : ?>
        <fieldset>
            <legend>Открытые задачи перейдут вместе с клиентами</legend>
            <?php foreach ($work['tasks'] as $task) : ?>
                <label class="d-block"><?= Html::checkbox('keep_tasks[]', false, ['value' => (int)$task->id]); ?>
                    Оставить у сотрудника: задача №<?= (int)$task->id; ?> · <?= Html::encode((string)$task->name); ?></label>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>
    <div class="form-check">
        <?= Html::checkbox('dismiss', false, ['id' => 'sx-crm-transfer-dismiss', 'value' => 1, 'class' => 'form-check-input']); ?>
        <label class="form-check-label" for="sx-crm-transfer-dismiss">Сотрудник увольняется: убрать его из этих клиентов и деактивировать</label>
        <p class="sx-lead-identity__note">Без отметки сотрудник остаётся участником клиентов с уровнем «Свои сделки и данные компании».</p>
    </div>
    <div class="sx-lead-hint__actions">
        <?= Html::submitButton('Передать', ['class' => 'sx-button sx-button--primary']); ?>
        <?= Html::button('Отмена', [
            'class' => 'sx-button sx-button--secondary',
            // As the standard form buttons (cms-backend form-buttons.js): close the backend window.
            'onclick' => 'if (sx.Window && sx.Window.openerWidget()) { sx.Window.openerWidget().close(); } else { history.back(); } return false;',
        ]); ?>
    </div>
<?= Html::endForm(); ?>
