<?php

$treeNodeView = file_get_contents(dirname(__DIR__).'/src/views/admin-tree/_tree-node.php');
$treeIndexView = file_get_contents(dirname(__DIR__).'/src/views/admin-tree/index.php');
$treeNodeWrapper = file_get_contents(dirname(__DIR__).'/src/widgets/tree/views/_node.php');
$treeView = file_get_contents(dirname(__DIR__).'/src/widgets/tree/views/tree.php');
$treeStyles = file_get_contents(dirname(__DIR__).'/src/widgets/tree/assets/src/css/style.css');

$expect = static function ($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, $message.PHP_EOL);
        exit(1);
    }
};

$expect(strpos($treeNodeView, 'AjaxControllerActionsWidget::widget') !== false, 'Tree actions do not use the standard AJAX actions menu.');
$expect(strpos($treeNodeView, 'ContextMenuControllerActionsWidget') === false, 'Legacy tree context-menu widget is still rendered.');
$expect(strpos($treeNodeView, "BackendIcon::render('edit'") !== false, 'Tree edit action does not use BackendIcon.');
$expect(strpos($treeNodeView, "BackendIcon::render('move-vertical'") !== false, 'Tree sorting action does not use BackendIcon.');
$expect(strpos($treeNodeView, "BackendIcon::render('file'") === false, 'Redundant tree-type icon is still rendered.');
$expect(strpos($treeNodeView, 'sx-btn-caret-action') === false, 'Legacy caret action remains in the tree.');
$expect(strpos($treeNodeWrapper, "BackendIcon::render('plus'") !== false, 'Tree expand control does not use BackendIcon.');
$expect(strpos($treeNodeWrapper, 'aria-hidden="<?= $model->children') !== false, 'Tree rows do not reserve a stable expand-control slot.');
$expect(strpos($treeView, "BackendIcon::render('search'") !== false, 'Tree search does not use the semantic backend icon.');
$expect(strpos($treeView, 'fas fa-search') === false, 'Legacy search icon remains in the tree.');
$expect(strpos($treeView, 'class="form-control"') === false, 'Tree search still inherits the legacy form-control focus border.');
$expect(strpos($treeIndexView, 'sx-tree-context-actions-anchor') !== false, 'Tree does not open the standard actions popover on right click.');
$expect(strpos($treeIndexView, 'class="col-md-12"') === false, 'Tree PJAX is still constrained by the legacy Bootstrap column padding.');
$expect(strpos($treeIndexView, 'class="sx-tree-page"') !== false, 'Tree page has no full-width layout wrapper.');
$expect(strpos($treeStyles, 'font-weight: 500') !== false, 'Tree node labels do not use the requested medium weight.');
$expect(strpos($treeStyles, '.sx-tree ul ul') !== false, 'Nested tree levels have no visual indentation.');
$expect(strpos($treeStyles, ".cms-tree-wrapper {\n    margin-left: 0;") !== false, 'Legacy tree wrapper offset remains.');
$expect(strpos($treeStyles, '.sx-tree-search:focus-within') !== false, 'Tree search has no unified focus state.');
$expect(strpos($treeStyles, 'cursor: pointer') !== false, 'Tree search button does not expose a pointer cursor.');
$expect(strpos($treeStyles, '.sx-container-tree > [data-pjax-container]') !== false, 'Tree PJAX container is not explicitly full width.');

echo "cms-tree-modern-actions: OK\n";
