<?php

function selectSortableExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$asset = file_get_contents(dirname(__DIR__).'/src/widgets/assets/DualSelectAsset.php');
$script = file_get_contents(dirname(__DIR__).'/src/widgets/assets/src/dual-select/dual-select.js');
$dualView = file_get_contents(dirname(__DIR__).'/src/widgets/views/dual-select.php');
$sortView = file_get_contents(dirname(__DIR__).'/src/widgets/views/sort-select.php');

selectSortableExpect(
    strpos($asset, 'BackendSortableAdapterAsset::class') !== false,
    'DualSelect asset does not declare the backend Sortable adapter dependency.'
);
selectSortableExpect(
    strpos($script, 'sx.backend.sortable.create(') !== false
    && strpos($script, 'this.jHidden.add(this.jVisible)') !== false
    && strpos($script, 'group: "sx-dual-select-" + this.get(\'id\')') !== false
    && strpos($script, 'itemSelector: "> li"') !== false,
    'DualSelect does not initialize a connected SortableJS group.'
);
selectSortableExpect(
    strpos($script, '.sortable(') === false
    && strpos($script, '.disableSelection(') === false,
    'DualSelect still uses jQuery UI methods directly.'
);
selectSortableExpect(
    substr_count($script, 'onUpdate: function()') === 1
    && strpos($script, 'self._update();') !== false,
    'DualSelect does not synchronize its hidden select after a completed move.'
);
selectSortableExpect(
    strpos($dualView, '\\yii\\jui\\Sortable::widget()') === false,
    'DualSelect view still registers jQuery UI Sortable.'
);
selectSortableExpect(
    strpos($sortView, '\\yii\\jui\\Sortable::widget()') === false
    && strpos($sortView, 'registerJs(') === false,
    'SortSelect still registers unused sortable assets or JavaScript.'
);

echo "CMS select sortable adapter contract: OK\n";
