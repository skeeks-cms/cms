<?php

$view = file_get_contents(dirname(__DIR__).'/src/views/admin-user/view.php');
$widget = file_get_contents(dirname(__DIR__).'/src/widgets/admin/CrmContactBlockWidget.php');

function userSurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

userSurfaceExpect(strpos($view, 'BackendSurfaceWidget::begin') !== false, 'User view does not use canonical surfaces.');
userSurfaceExpect(substr_count($view, 'sx-surface-stack') >= 3, 'User view columns and activity feed do not use canonical surface spacing.');
userSurfaceExpect(strpos($view, 'sx-block') === false, 'User view still contains deprecated sx-block markup.');
userSurfaceExpect(strpos($view, 'sx-panel') === false, 'User view still contains deprecated sx-panel markup.');
userSurfaceExpect(strpos($view, 'sx-value-row') === false, 'User view still depends on deprecated block value rows.');
userSurfaceExpect(strpos($view, 'sx-edit-btn') === false, 'User view still depends on deprecated block edit actions.');
// Surface hints are rendered by the shared CRM contact block widget.
userSurfaceExpect(strpos($view, 'CrmContactBlockWidget::hintIcon($tooltip)') !== false, 'User surface hints do not use the shared CRM hint icon.');
userSurfaceExpect(strpos($widget, "'data-container' => 'body'") !== false, 'CRM hint tooltips are not escaped from the drawer stacking context.');
userSurfaceExpect(strpos($widget, "'tabindex' => '0'") !== false, 'CRM hint icons are not keyboard focusable.');

echo "CMS user surface migration contract: OK\n";
