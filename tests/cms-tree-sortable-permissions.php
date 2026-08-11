<?php

function treeSortablePermissionsExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$treeView = file_get_contents(dirname(__DIR__).'/src/views/admin-tree/index.php');

treeSortablePermissionsExpect(
    strpos($treeView, "\$canResort = \\Yii::\$app->user->can('cms/admin-tree/resort');") !== false,
    'CMS tree does not resolve the resort permission before registering assets.'
);
treeSortablePermissionsExpect(
    preg_match('/if \(\$canResort\) \{\s*\\\\skeeks\\\\cms\\\\backend\\\\widgets\\\\sortable\\\\assets\\\\BackendSortableAdapterAsset::register\(\$this\);\s*\}/', $treeView) === 1,
    'CMS tree registers the Sortable adapter without the resort permission guard.'
);
treeSortablePermissionsExpect(
    strpos($treeView, '\\yii\\jui\\Sortable::widget()') === false,
    'CMS tree still registers the jQuery UI Sortable provider.'
);
treeSortablePermissionsExpect(
    strpos($treeView, "if (this.get('canResort')) {") !== false,
    'CMS tree initializes Sortable without the resort permission guard.'
);
treeSortablePermissionsExpect(
    strpos($treeView, 'sx.backend.sortable.create(') !== false,
    'CMS tree does not initialize the backend Sortable adapter.'
);
treeSortablePermissionsExpect(
    strpos($treeView, '.find("ul").sortable(') === false,
    'CMS tree still initializes jQuery UI Sortable directly.'
);
treeSortablePermissionsExpect(
    strpos($treeView, 'onUpdate: function(event)') !== false
    && strpos($treeView, 'var Jul = event.jContainer;') !== false
    && strpos($treeView, 'event.jItem.data("id")') !== false,
    'CMS tree does not use the normalized Sortable adapter update event.'
);

echo "CMS tree sortable permission contract: OK\n";
