<?php
/**
 * «Клиенты в CRM» of an employee profile (owner's decision 29.10.2026):
 * the right to the general pool (CrmAccess::canUsePool(): administrators and
 * heads of departments always; one employee — here, by an administrator or his
 * head; a role — in «Роли») and «Новые компании и клиенты, которые я создаю, —
 * „Всегда по согласованию“» (CrmProtection::onCreated()). Seen by the employee,
 * his head and an administrator (CrmAccess::canEditCrmSettings()).
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsUser $model
 */

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmProtection;
use yii\helpers\Html;
use yii\helpers\Url;

$access = CrmAccess::instance();
$actorId = (int)\Yii::$app->user->id;
if (!$model->is_worker || !CrmAccess::mainReady() || !$access->canEditCrmSettings($actorId, (int)$model->id)) {
    return;
}
$workerId = (int)$model->id;
if ($access->isAdmin($workerId)) {
    $poolSource = 'администратор';
} elseif ($access->isDepartmentHead($workerId)) {
    $poolSource = 'руководитель отдела';
} elseif ($access->canUsePool($workerId) && !$access->hasOwnPoolRight($workerId)) {
    $poolSource = 'по роли';
} else {
    $poolSource = null;
}
$canGrant = $poolSource === null && $access->canGrantPool($actorId, $workerId);
$url = Url::to(['crm-settings', 'pk' => $workerId]);

$this->registerJs(<<<JS
$(document).off('change.sxCrmSettings').on('change.sxCrmSettings', '[data-sx-crm-setting]', function () {
    var input = $(this);
    var data = {op: input.data('sxCrmSetting'), value: input.is(':checked') ? 1 : 0};
    data[yii.getCsrfParam()] = yii.getCsrfToken();
    input.prop('disabled', true);
    $.post(input.data('url'), data, null, 'json').done(function (response) {
        if (response && response.success) {
            sx.notify.success(response.message);
        } else {
            sx.notify.error(response && response.message ? response.message : 'Не удалось сохранить.');
            input.prop('checked', !input.is(':checked'));
        }
    }).fail(function () {
        sx.notify.error('Не удалось сохранить. Обновите страницу.');
        input.prop('checked', !input.is(':checked'));
    }).always(function () {
        input.prop('disabled', false);
    });
});
JS
);
?>
<div class="sx-surface sx-surface--raised sx-surface--padded">
    <div class="sx-surface__title">Клиенты в CRM</div>
    <div class="form-check mb-2">
        <?= Html::checkbox('crm_pool', $poolSource !== null || $access->hasOwnPoolRight($workerId), [
            'id' => 'sx-crm-setting-pool',
            'class' => 'form-check-input',
            'disabled' => !$canGrant,
            'data-sx-crm-setting' => 'pool',
            'data-url' => $url,
        ]); ?>
        <label class="form-check-label" for="sx-crm-setting-pool">
            Видит общий пул и берёт клиентов в работу
            <?php if ($poolSource !== null) : ?>
                <span class="sx-text--muted">— всегда: <?= Html::encode($poolSource); ?></span>
            <?php elseif (!$canGrant) : ?>
                <span class="sx-text--muted">— выдаёт администратор или руководитель</span>
            <?php endif; ?>
        </label>
    </div>
    <?php if (CrmProtection::newAlwaysReady()) : ?>
        <div class="form-check">
            <?= Html::checkbox('crm_new_always', (bool)$model->crm_new_always, [
                'id' => 'sx-crm-setting-new-always',
                'class' => 'form-check-input',
                'data-sx-crm-setting' => 'new_always',
                'data-url' => $url,
            ]); ?>
            <label class="form-check-label" for="sx-crm-setting-new-always">
                Новые компании и клиенты, которые <?= $actorId === $workerId ? 'я создаю' : 'создаёт сотрудник'; ?>, — «Всегда по согласованию»
            </label>
        </div>
    <?php endif; ?>
</div>
