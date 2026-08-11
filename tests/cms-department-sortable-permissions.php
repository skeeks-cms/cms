<?php

function departmentSortablePermissionsExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$departmentView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-department/index.php');

departmentSortablePermissionsExpect(
    strpos($departmentView, '$canResort = \\Yii::$app->user->can(\\skeeks\\cms\\rbac\\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);') !== false,
    'Department tree does not resolve the admin permission before registering assets.'
);
departmentSortablePermissionsExpect(
    preg_match('/if \(\$canResort\) \{\s*\\\\skeeks\\\\cms\\\\backend\\\\widgets\\\\sortable\\\\assets\\\\BackendSortableAdapterAsset::register\(\$this\);\s*\}/', $departmentView) === 1,
    'Department tree registers the Sortable adapter without the admin permission guard.'
);
departmentSortablePermissionsExpect(
    strpos($departmentView, '\\yii\\jui\\Sortable::widget()') === false,
    'Department tree still registers the jQuery UI Sortable provider.'
);
departmentSortablePermissionsExpect(
    strpos($departmentView, "if (this.get('canResort')) {") !== false,
    'Department tree initializes Sortable without the admin permission guard.'
);
departmentSortablePermissionsExpect(
    strpos($departmentView, 'sx.backend.sortable.create(') !== false
    && strpos($departmentView, '.find("ul").sortable(') === false,
    'Department tree does not use the shared backend Sortable adapter.'
);
departmentSortablePermissionsExpect(
    strpos($departmentView, 'onUpdate: function(event)') !== false
    && strpos($departmentView, 'var Jul = event.jContainer;') !== false
    && strpos($departmentView, 'event.jItem.data("id")') !== false,
    'Department tree does not use the normalized Sortable adapter update event.'
);

echo "CMS department sortable permission contract: OK\n";
