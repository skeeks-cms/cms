<?php

$taskView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-task/view.php');

function taskProjectClientVisibilityExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

taskProjectClientVisibilityExpect(
    strpos($taskView, '$projectClients = $model->cmsProject ? $model->cmsProject->users : [];') !== false,
    'Task view does not resolve clients through the related project.'
);
taskProjectClientVisibilityExpect(
    strpos($taskView, '<?php if ($projectClients) : ?>') !== false,
    'Task view does not hide the client warning when the project has no clients.'
);
taskProjectClientVisibilityExpect(
    strpos($taskView, 'Эта задача доступна перечисленным клиентам') !== false,
    'Task view does not explain why project clients can see the task.'
);
taskProjectClientVisibilityExpect(
    strpos($taskView, "'controllerId' => '/cms/admin-user'") !== false,
    'Task view does not link the visible clients to their user cards.'
);

echo "CMS task project-client visibility contract: OK\n";
