<?php

function activitySurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$itemView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/_log-list-item.php');
$listView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/log-list.php');
$activityCss = file_get_contents(dirname(__DIR__).'/src/widgets/assets/src/cms-activity/cms-activity.css');

foreach ([$itemView, $listView] as $view) {
    activitySurfaceExpect(strpos($view, 'sx-block') === false, 'CMS activity view still emits deprecated block markup.');
}
activitySurfaceExpect(strpos($itemView, 'sx-surface sx-surface--padded') !== false, 'CMS activity item does not use the canonical surface.');
activitySurfaceExpect(strpos($listView, 'sx-surface sx-surface--padded') !== false, 'CMS activity empty state does not use the canonical surface.');
activitySurfaceExpect(strpos($activityCss, '.sx-log-list .sx-log-item {') !== false, 'CMS activity item spacing owner is missing.');
activitySurfaceExpect(strpos($activityCss, 'margin-bottom: 1rem;') !== false, 'CMS activity item spacing was not preserved.');

echo "CMS activity surface contract: OK\n";
