<?php

function activitySurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$itemView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/_log-list-item.php');
$listView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/log-list.php');
$commentView = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/comment.php');
$logListWidget = file_get_contents(dirname(__DIR__).'/src/widgets/admin/CmsLogListWidget.php');
$activityJs = file_get_contents(dirname(__DIR__).'/src/widgets/assets/src/cms-activity/cms-activity.js');
$activityCss = file_get_contents(dirname(__DIR__).'/src/widgets/assets/src/cms-activity/cms-activity.css');

foreach ([$itemView, $listView] as $view) {
    activitySurfaceExpect(strpos($view, 'sx-block') === false, 'CMS activity view still emits deprecated block markup.');
}
activitySurfaceExpect(strpos($itemView, 'sx-surface sx-surface--padded') !== false, 'CMS activity item does not use the canonical surface.');
activitySurfaceExpect(strpos($listView, 'sx-surface sx-surface--padded') !== false, 'CMS activity empty state does not use the canonical surface.');
activitySurfaceExpect(strpos($activityCss, '.sx-log-list .sx-log-item {') !== false, 'CMS activity item spacing owner is missing.');
activitySurfaceExpect(strpos($activityCss, 'margin-bottom: 1rem;') !== false, 'CMS activity item spacing was not preserved.');
activitySurfaceExpect(substr_count($commentView, 'activeHiddenInput($log') === 3, 'CMS comment context is not rendered as hidden inputs.');
activitySurfaceExpect(strpos($commentView, 'class="d-none"') === false, 'CMS comment context still depends on Bootstrap visibility utilities.');
activitySurfaceExpect(strpos($commentView, 'class="row"') === false, 'CMS comment form still depends on the Bootstrap grid.');
activitySurfaceExpect(strpos($listView, 'col-md-12') === false, 'CMS activity list still depends on Bootstrap columns.');
activitySurfaceExpect(strpos($itemView, 'd-flex') === false, 'CMS activity item still depends on Bootstrap flex utilities.');
activitySurfaceExpect(strpos($activityCss, '.sx-log-list .sx-log-item__header {') !== false, 'CMS activity header layout owner is missing.');
activitySurfaceExpect(strpos($commentView, 'sx-button sx-button--secondary sx-button--sm sx-comment-pin-toggle') !== false, 'CMS comment pin action does not use the standard secondary button.');
activitySurfaceExpect(strpos($commentView, 'sx-chip sx-comment-pin-toggle') === false, 'CMS comment pin action still uses chip styling.');
activitySurfaceExpect(strpos($activityCss, '.sx-comment-pin-toggle:focus:not(:focus-visible)') !== false, 'CMS comment pin action does not suppress pointer-only focus outline.');
activitySurfaceExpect(strpos($logListWidget, 'public $is_raised = true;') !== false, 'Standalone CMS activity items are not raised by default.');
activitySurfaceExpect(strpos($itemView, "' sx-surface--raised'") !== false, 'CMS activity item does not expose the raised surface modifier.');
activitySurfaceExpect(strpos($itemView, 'sx-button sx-button--secondary sx-button--sm sx-log-pin-toggle') !== false, 'Existing CMS log pin action does not use the standard secondary button.');
activitySurfaceExpect(strpos($itemView, 'sx-chip sx-chip--compact sx-log-pin-toggle') === false, 'Existing CMS log pin action still uses chip styling.');
activitySurfaceExpect(strpos($activityJs, 'event.originalEvent.detail > 0') !== false, 'Pointer activation does not release the decorative comment pin focus state.');

echo "CMS activity surface contract: OK\n";
