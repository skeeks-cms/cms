<?php

$view = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-company/view.php');
$widget = file_get_contents(dirname(__DIR__).'/src/widgets/admin/CrmContactBlockWidget.php');

function companySurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

companySurfaceExpect(strpos($view, 'BackendSurfaceWidget::begin') !== false, 'Company view does not use canonical surfaces.');
companySurfaceExpect(strpos($view, 'sx-surface-stack') !== false, 'Company view does not use canonical surface spacing.');
companySurfaceExpect(strpos($view, 'sx-block') === false, 'Company view still contains deprecated sx-block markup.');
companySurfaceExpect(strpos($view, 'sx-value-row') === false, 'Company view still depends on deprecated block value rows.');
companySurfaceExpect(strpos($view, 'sx-edit-btn') === false, 'Company view still depends on deprecated block edit actions.');
// Surface hints are rendered by the shared CRM contact block widget.
companySurfaceExpect(strpos($view, 'CrmContactBlockWidget::hintIcon($tooltip)') !== false, 'Company surface hints do not use the shared CRM hint icon.');
companySurfaceExpect(strpos($widget, "'data-toggle' => 'tooltip'") !== false, 'CRM hint icons do not use the backend tooltip contract.');
companySurfaceExpect(strpos($widget, "'data-container' => 'body'") !== false, 'CRM hint tooltips are not escaped from the drawer stacking context.');
companySurfaceExpect(strpos($widget, "'tabindex' => '0'") !== false, 'CRM hint icons are not keyboard focusable.');
companySurfaceExpect(strpos($widget, "'aria-label' => \$hint") !== false, 'CRM hint icons do not expose an accessible label.');

echo "CMS company surface migration contract: OK\n";
