<?php
/**
 * «Ответственный» of a new deal (П2а): by default the employee who creates
 * it; a client he sees but does not work on sends the deal to its
 * responsible (CrmAccess::defaultExecutorId()). The note tells the rules of a
 * foreign client (LeadTriage::workWithClient()): the pool is taken, a free one
 * gives "Свои сделки", a protected one sends a request.
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
    <p class="sx-lead-identity__note">Клиент из общего пула: вы берёте его в работу и становитесь основным ответственным.
        Клиент другого сотрудника со свободной защитой: сделка ваша, доступ «Свои сделки», основной ответственный получит уведомление.
        Клиент под защитой: уйдёт запрос основному ответственному, сделка появится после одобрения.
        Клиент, которого вы видите, но с ним не работаете: по умолчанию сделка уйдёт его ответственному.</p>
</div>
