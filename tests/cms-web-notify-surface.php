<?php

function webNotifySurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$view = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/web-notify.php');
$widget = file_get_contents(dirname(__DIR__).'/src/widgets/admin/CmsWebNotifyWidget.php');
$css = file_get_contents(dirname(__DIR__).'/src/widgets/assets/src/web-notify/web-notify.css');
$notifyView = strstr($view, '<div id="sx-stale-work-modal"', true);

webNotifySurfaceExpect(
    strpos($view, 'sx-notifies sx-surface sx-surface--raised sx-surface--clip') !== false,
    'Web notify popup does not use the canonical raised surface.'
);
webNotifySurfaceExpect(strpos($view, 'sx-surface__body') !== false, 'Web notify popup has no canonical surface body.');
webNotifySurfaceExpect(strpos($view, 'sx-surface__footer') !== false, 'Web notify popup has no canonical surface footer.');
webNotifySurfaceExpect(strpos($view, 'sx-button sx-button--primary sx-button--sm') !== false, 'Web notify actions do not use semantic backend buttons.');
webNotifySurfaceExpect(strpos($view, 'sx-trigger-notifies sx-shell-header__action sx-shell-header__action--icon') !== false, 'Web notify trigger does not use the shared header action contract.');
webNotifySurfaceExpect(strpos($notifyView, 'btn btn-primary') === false, 'Web notify popup still depends on Bootstrap buttons.');
webNotifySurfaceExpect(strpos($notifyView, 'class="d-block sx-trigger-notifies"') === false, 'Web notify trigger still depends on a Bootstrap display utility.');
webNotifySurfaceExpect(strpos($css, '.sx-notifies .sx-notifies-list .sx-item-inner {') !== false, 'Web notify item layout owner is missing.');
webNotifySurfaceExpect(strpos($css, '.sx-notifies .sx-notifies-list .sx-item .sx-model {') !== false, 'Web notify model typography owner is missing.');
webNotifySurfaceExpect(strpos($css, 'overflow-wrap: anywhere;') !== false, 'Long notification titles are not protected from overflow.');
webNotifySurfaceExpect(strpos($css, 'width: 100%;') !== false, 'Entity links can collapse to the global icon-action width.');
webNotifySurfaceExpect(strpos($css, 'text-decoration: underline;') === false, 'Notification entity links should use color, not underlining, for interaction feedback.');
webNotifySurfaceExpect(strpos($widget, 'public $enableWorkReminders = false;') !== false, 'Work reminders are not opt-in for client cabinets.');
webNotifySurfaceExpect(strpos($view, "'enable_work_reminders' => (bool)\$widget->enableWorkReminders") !== false, 'Work reminder option is not passed to the client component.');
webNotifySurfaceExpect(strpos($view, '<?php if ($widget->enableWorkReminders): ?>') !== false, 'Admin-only reminder dialogs are always rendered.');

echo "CMS web notify surface contract: OK\n";
