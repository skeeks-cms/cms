<?php
/**
 * A client without the main responsible (owner's decision 29.10.2026, п. 13а:
 * there is no general pool; old data until `cms/crm/assign-pool-main`): the
 * card shows it to who sees it (an administrator, a head of a department —
 * CrmAccess::canSeeOrphan()) with the way to give it a main one («Доступ»).
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 * @var string $controllerRoute
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCompany;
use yii\helpers\Html;
use yii\helpers\Url;

$code = $model instanceof CmsCompany ? 'company' : 'client';
$access = CrmAccess::instance();
if (!$access->isOrphan($code, (int)$model->id)) {
    return;
}
?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Клиент без основного ответственного',
    'titleTag' => 'h2',
    'raised' => true,
    'responsive' => true,
]); ?>
    <p class="sx-lead-identity__note">У каждого клиента должен быть основной ответственный. Назначьте его во вкладке «Доступ» или массовым действием в списке компаний.</p>
    <?php if ($access->canManageAccess((int)\Yii::$app->user->id, $code, (int)$model->id)) : ?>
        <div class="sx-lead-hint__actions">
            <?= Html::a('Назначить основного', Url::current([0 => $controllerRoute.'/crm-access']), ['class' => 'sx-button sx-button--primary', 'data-pjax' => 0]); ?>
        </div>
    <?php endif; ?>
<?php BackendSurfaceWidget::end(); ?>
