<?php

$basePath = dirname(__DIR__).'/src';
$model = file_get_contents($basePath.'/models/CmsProject.php');
$controller = file_get_contents($basePath.'/controllers/AdminCmsProjectController.php');
$view = file_get_contents($basePath.'/views/admin-cms-project/view.php');
$migration = file_get_contents($basePath.'/migrations/m260812_151600__alter_table__cms_project__add_work_time_visibility.php');

function projectWorkTimeVisibilityExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$attribute = 'is_work_time_visible_for_clients';

projectWorkTimeVisibilityExpect(strpos($model, $attribute) !== false, 'Project model does not expose the work-time visibility setting.');
projectWorkTimeVisibilityExpect(strpos($controller, "'{$attribute}' => [") !== false, 'Project form does not contain the work-time visibility setting.');
projectWorkTimeVisibilityExpect(strpos($view, '$model->'.$attribute) !== false, 'Project view does not display the work-time visibility setting.');
projectWorkTimeVisibilityExpect(strpos($migration, "defaultValue(0)") !== false, 'Work-time visibility must be disabled by default.');

echo "CMS project work-time visibility contract: OK\n";
