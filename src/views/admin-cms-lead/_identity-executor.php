<?php
/**
 * «Ответственный» of a new deal (П2а): by default the employee who creates
 * it; a client of another responsible sends the deal to that responsible.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\crm\LeadIdentityForm $form
 */

use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;

$formName = $form->formName();
?>
<div class="sx-lead-identity__executor">
    <?= AjaxSelectModel::widget([
        'id' => 'sx-lead-identity-executor',
        'name' => $formName.'[executor_id]',
        'value' => $form->executor_id,
        'modelClass' => CmsUser::class,
        'modelShowAttribute' => 'shortDisplayName',
        'searchQuery' => static function ($word = '') {
            $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1]);
            return $word ? $query->search($word) : $query;
        },
        'options' => ['placeholder' => 'По умолчанию — ответственный клиента или вы'],
        'pluginOptions' => ['allowClear' => true],
    ]); ?>
    <p class="sx-lead-identity__note">Если клиент закреплён за другим сотрудником, сделка уйдёт его ответственному.</p>
</div>
