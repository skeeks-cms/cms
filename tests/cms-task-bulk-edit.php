<?php

function taskBulkEditExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__).'/src';
$controller = file_get_contents($root.'/controllers/AdminCmsTaskController.php');
$form = file_get_contents($root.'/forms/CmsTaskBulkEditForm.php');
$relatedAction = file_get_contents(dirname(dirname(__DIR__)).'/cms-backend/src/actions/BackendGridModelRelatedAction.php');

taskBulkEditExpect(
    strpos($controller, "'class' => BackendModelMultiWindowAction::class") !== false
        && strpos($controller, "'name' => 'Редактирование'") !== false,
    'Task editing must use the reusable iframe multi-action with the generic edit label.'
);
taskBulkEditExpect(
    strpos($controller, "'buttons' => ['save']") !== false,
    'Task bulk editing must show one unambiguous save button.'
);
taskBulkEditExpect(
    strpos($controller, "'modelsQueryCallback'") !== false
        && strpos($controller, 'self::initQuery($query)') !== false,
    'The bulk action must retain the manager-visible task query scope.'
);
taskBulkEditExpect(
    strpos($controller, '$form->executor_id !== null') !== false
        && strpos($controller, '$form->plan_duration !== null') !== false
        && strpos($controller, '$form->fact_duration !== null') !== false,
    'Executor and both durations must be applied only when explicitly supplied.'
);
taskBulkEditExpect(
    strpos($controller, '$form->hasRelationChange()') !== false
        && strpos($controller, '$model->cms_user_id = (int)$form->cms_user_id;') !== false,
    'Bulk editing must support mutually exclusive company, client and project relations.'
);
taskBulkEditExpect(
    strpos($controller, "'widgetClass' => SmartDurationInputWidget::class") !== false
        && strpos($controller, 'data-relation-type="company"') !== false
        && strpos($controller, 'data-relation-type="client"') !== false
        && strpos($controller, 'data-relation-type="project"') !== false,
    'Bulk editing must reuse task duration controls and expose all three relation tabs.'
);
taskBulkEditExpect(
    strpos($controller, "'task_relation_open' =>") < strpos($controller, "'executor_id' => [", strpos($controller, 'public function bulkEditFields'))
        && strpos($controller, "'executor_id' => [", strpos($controller, 'public function bulkEditFields')) < strpos($controller, "'plan_duration' => [", strpos($controller, 'public function bulkEditFields'))
        && strpos($controller, "'plan_duration' => [", strpos($controller, 'public function bulkEditFields')) < strpos($controller, "'fact_duration' => [", strpos($controller, 'public function bulkEditFields')),
    'Bulk fields must be ordered as relation, executor, planned duration and report duration.'
);
taskBulkEditExpect(
    strpos($form, 'public $executor_id;') !== false
        && strpos($form, 'public $plan_duration;') !== false
        && strpos($form, 'public $fact_duration;') !== false
        && strpos($form, 'public $cms_user_id;') !== false,
    'The bulk form must expose all requested optional attributes.'
);
taskBulkEditExpect(
    strpos($form, "return \$value === '' ? null : \$value;") !== false
        && strpos($form, 'Укажите хотя бы одно значение для редактирования.') !== false,
    'Empty values must mean keep the existing value while an entirely empty form is rejected.'
);
taskBulkEditExpect(
    strpos($form, "'cms_company_id' => (int)\$this->cms_company_id") !== false,
    'A selected company project must be validated against its company.'
);
taskBulkEditExpect(
    strpos($relatedAction, 'passRelationContextToMultiWindowActions') !== false
        && strpos($relatedAction, '$this->getBindRelation($this->model)') !== false
        && strpos($relatedAction, 'instanceof BackendModelMultiWindowAction') !== false,
    'Every related collection must pass its bound parent relation into iframe multi-actions.'
);
taskBulkEditExpect(
    strpos($controller, "request->get('cms_company_id')") !== false
        && strpos($controller, "request->get('cms_user_id')") !== false
        && strpos($controller, "request->get('cms_project_id')") !== false
        && strpos($controller, '$project->cms_company_id') !== false,
    'Task bulk editing must initialize company, client and project contexts, including a project company.'
);

echo "CMS task bulk edit contract: OK\n";
