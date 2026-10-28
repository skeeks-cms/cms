<?php
/**
 * The top of a card of the general pool (П2д): the client has no employee; a
 * CRM employee sees its data and its own feed read only and takes it into work
 * (ClientTransferActions::clientTake()) — then he is the main responsible and
 * the card opens in full. A protected client without employees is assigned by
 * a leader or an administrator. An administrator sees the card in full anyway
 * (CrmAccess::isAdmin()), the banner only offers to take it.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 * @var string $controllerRoute e.g. "/cms/admin-cms-company"
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCompany;
use yii\helpers\Html;
use yii\helpers\Url;

$code = $model instanceof CmsCompany ? 'company' : 'client';
$access = CrmAccess::instance();
if (!$access->inPool($code, (int)$model->id)) {
    return;
}
\skeeks\cms\assets\admin\LeadIdentityAsset::register($this);
$canTake = $access->canTakeIntoWork($code, (int)$model->id);
?>
<?php BackendSurfaceWidget::begin([
    'title' => $canTake ? 'Свободный клиент: общий пул' : 'Клиент без ответственного',
    'titleTag' => 'h2',
    'raised' => true,
    'responsive' => true,
    'options' => ['class' => 'sx-crm-pool'],
]); ?>
    <?php if ($canTake) : ?>
        <?php if ($access->isAdmin((int)\Yii::$app->user->id)) : ?>
            <p class="sx-lead-identity__note">С клиентом пока никто не работает. Администратору карточка открыта полностью; взять клиента в работу можно здесь, назначить другого ответственного — во вкладке «Доступ».</p>
        <?php else : ?>
            <p class="sx-lead-identity__note">С клиентом пока никто не работает. Возьмите его в работу — вы станете основным ответственным, и карточка откроется полностью: сделки, счета, правка данных.</p>
        <?php endif; ?>
        <div class="sx-lead-hint__actions">
            <?= Html::button('Взять в работу', [
                'class' => 'sx-button sx-button--primary',
                'data-sx-crm-access' => 'take',
                'data-url' => Url::current([0 => $controllerRoute.'/crm-take']),
                'data-sx-confirm' => 'Взять клиента в работу? Вы станете его основным ответственным.',
            ]); ?>
        </div>
    <?php else : ?>
        <p class="sx-lead-identity__note"><?= Html::encode(\skeeks\cms\crm\CrmProtection::describe($code, (int)$model->id)); ?>. Ответственного назначает руководитель или администратор.</p>
    <?php endif; ?>
<?php BackendSurfaceWidget::end(); ?>
