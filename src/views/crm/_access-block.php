<?php
/**
 * «Работают с компанией» / «Работают с клиентом» in the profile of a card:
 * the main responsible first («Основной»), each employee with the level in
 * words (the same row as «Доступ», views/crm/_access-row.php). Who manages
 * the access: «+ Добавить сотрудника» (the same window) and a cross to remove;
 * others read only. «Настроить доступ →» opens the tab «Доступ».
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 * @var string $controllerRoute e.g. "/cms/admin-cms-company"
 * @var array $surface BackendSurfaceWidget config of the card
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use yii\helpers\Html;
use yii\helpers\Url;

if (!CrmAccess::mainReady()) {
    return;
}
$code = $model instanceof CmsCompany ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT;
$access = CrmAccess::instance();
$rows = $access->accessRows($code, (int)$model->id);
$canManage = $access->canManageAccess((int)\Yii::$app->user->id, $code, (int)$model->id);
if (!$rows && !$canManage) {
    return;
}
// The card may be open in a backend window: its parameters stay (Url::current()).
$accessUrl = Url::current([0 => $controllerRoute.'/crm-access', 'pk' => (int)$model->id]);
\skeeks\cms\assets\admin\LeadIdentityAsset::register($this);
?>
<?php BackendSurfaceWidget::begin($surface); ?>
    <ul class="sx-crm-access__list">
        <?php foreach ($rows as $employee) : ?>
            <?= $this->render('@skeeks/cms/views/crm/_access-row', [
                'employee' => $employee, 'mode' => 'card', 'canManage' => $canManage, 'accessUrl' => $accessUrl,
            ]); ?>
        <?php endforeach; ?>
    </ul>
    <?php if ($canManage) : ?>
        <?= $this->render('@skeeks/cms/views/crm/_access-add-button', ['controllerRoute' => $controllerRoute, 'recordId' => (int)$model->id]); ?>
    <?php endif; ?>
    <?= Html::a('Настроить доступ →', $accessUrl, ['class' => 'sx-crm-access__more', 'data-pjax' => '0']); ?>
<?php BackendSurfaceWidget::end(); ?>
