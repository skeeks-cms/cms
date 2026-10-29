<?php
/**
 * «Клиенты в CRM» of an employee profile (owner's decision 29.10.2026):
 * «Новые компании и клиенты, которые я создаю, — „Всегда по согласованию“»
 * (CrmProtection::onCreated(); he, his head, an administrator —
 * CrmAccess::canEditCrmSettings()) and, for his head or an administrator, the
 * clients he is the main one of with «Передать всех клиентов»
 * (crm-transfer-clients, also the dismissal).
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
$url = Url::to(['crm-settings', 'pk' => $workerId]);
$clients = $access->mainClientsCount($workerId);
$canTransfer = $clients && $access->canTransferClientsOf($actorId, $workerId);

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
    <p class="mb-2">Основной ответственный у клиентов: <strong><?= (int)$clients; ?></strong></p>
    <?php if ($canTransfer) : ?>
        <?php $transferAction = ['isOpenNewWindow' => true, 'size' => 'small', 'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(
            ['crm-transfer-clients', 'pk' => $workerId])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url]; ?>
        <p class="mb-2"><?= Html::a('Передать всех клиентов', '#', [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-pjax' => 0,
            'onclick' => 'new sx.classes.backend.widgets.Action('.\yii\helpers\Json::encode($transferAction).').go(); return false;',
        ]); ?></p>
    <?php endif; ?>
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
