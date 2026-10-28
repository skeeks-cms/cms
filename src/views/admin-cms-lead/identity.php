<?php
/**
 * «Клиент сделки» for a deal in work: find or create its company and contact
 * person (П2а). LeadTriageActions::identity().
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 * @var skeeks\cms\crm\LeadIdentityForm $form
 * @var array[] $candidates
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Клиент сделки №'.(int)$model->id;
?>
<?= Html::beginForm(Url::to(['/cms/admin-cms-lead/identity', 'pk' => $model->id]), 'post', ['data-sx-lead-ajax-form' => true]); ?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Кто клиент',
    'titleTag' => 'h2',
    'hint' => 'Выберите компанию и контакт из базы или создайте новые. Ответственный сделки не меняется.',
    'raised' => true,
    'responsive' => true,
]); ?>
    <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity', [
        'form' => $form,
        'lead' => $model,
        'candidates' => $candidates,
        'allowForeign' => false,
        'withExecutor' => false,
        'withContacts' => true,
    ]); ?>
    <div class="sx-lead-triage__actions">
        <?= Html::submitButton('Сохранить', ['class' => 'sx-button sx-button--primary']); ?>
    </div>
<?php BackendSurfaceWidget::end(); ?>
<?= Html::endForm(); ?>
