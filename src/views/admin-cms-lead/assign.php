<?php
/**
 * «Поручить»: the deal to another employee; the company keeps its main
 * responsible, the new one gets "Свои сделки" when he has no link
 * (CrmAccess::grantLeadExecutorLevel(), owner's decision 29.10.2026).
 * LeadTriageActions::assign().
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$this->title = 'Сменить ответственного: сделка №'.(int)$model->id;
?>
<?= Html::beginForm(Url::to(['/cms/admin-cms-lead/assign', 'pk' => $model->id]), 'post', ['data-sx-lead-ajax-form' => true]); ?>
<?php BackendSurfaceWidget::begin([
    'title' => 'Сменить ответственного',
    'titleTag' => 'h2',
    'hint' => 'Сделку получит выбранный сотрудник. Если у него ещё нет доступа к клиенту сделки, он получит доступ «Свои сделки»: увидит карточку клиента и будет работать с этой сделкой. Основной ответственный клиента и доступы других сотрудников не меняются.',
    'raised' => true,
    'responsive' => true,
]); ?>
    <div class="sx-lead-identity__part">
        <?= AjaxSelectModel::widget([
            'id' => 'sx-lead-assign-executor',
            'name' => 'executor_id',
            'value' => $model->executor_id,
            'modelClass' => CmsUser::class,
            'modelShowAttribute' => 'shortDisplayName',
            'searchQuery' => static function ($word = '') {
                $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1]);
                return $word ? $query->search($word) : $query;
            },
            'options' => ['placeholder' => 'Сотрудник'],
        ]); ?>
        <?= Html::textarea('comment', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Комментарий (необязательно)']); ?>
    </div>
    <div class="sx-lead-triage__actions">
        <?= Html::submitButton('Сохранить', ['class' => 'sx-button sx-button--primary']); ?>
    </div>
<?php BackendSurfaceWidget::end(); ?>
<?= Html::endForm(); ?>
