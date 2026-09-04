<?php

$widget = file_get_contents(__DIR__.'/../src/telephony/widgets/TelephonyWidget.php');
$script = file_get_contents(__DIR__.'/../src/telephony/widgets/assets/src/telephony.js');
$styles = file_get_contents(__DIR__.'/../src/telephony/widgets/assets/src/telephony.css');
$controller = file_get_contents(__DIR__.'/../src/controllers/TelephonyController.php');
$companyView = file_get_contents(__DIR__.'/../src/views/admin-cms-company/view.php');
$clientView = file_get_contents(__DIR__.'/../src/views/admin-user/view.php');
$leadView = file_get_contents(__DIR__.'/../src/views/admin-cms-lead/view.php');

$checks = [
    'polling uses recursive timeouts' => strpos($script, 'setTimeout(function ()') !== false,
    'polling does not use intervals' => strpos($script, 'setInterval(') === false,
    'only one polling request may run' => strpos($script, 'if (self.isPolling)') !== false,
    'next poll waits for the request chain' => strpos($script, "self.pollingRequest = self._get(self.get('urls').incoming)") !== false,
    'polling requests stay asynchronous' => strpos($script, 'async: true') !== false,
    'polling does not trigger global ajax blockers' => strpos($script, 'global: false') !== false,
    'incoming polling releases the PHP session lock' => preg_match('/actionIncoming\(\).*?releaseSessionLock\(\)/s', $controller) === 1,
    'status polling releases the PHP session lock' => preg_match('/actionStatus\(\).*?releaseSessionLock\(\)/s', $controller) === 1,
    'incoming polling is scoped to the current telephony user' => preg_match('/actionIncoming\(\).*?scopeCallsForTelephonyUser\(/s', $controller) === 1,
    'status polling is scoped to the current telephony user' => preg_match('/actionStatus\(\).*?scopeCallsForTelephonyUser\(/s', $controller) === 1,
    'call cancellation is scoped before the provider request' => preg_match('/actionCancel\(\).*?scopeCallsForTelephonyUser\(.*?if \(!\$call\).*?->cancel\(/s', $controller) === 1,
    'telephony scope accepts only the employee or their extension' => strpos($controller, "'.cms_worker_user_id' => Yii::\$app->user->id") !== false
        && strpos($controller, "'.provider_user_num' => \$providerUserNum") !== false,
    'incoming polling has no provider-wide fallback' => strpos($controller, '$callQuery()->one()') === false,
    'widget resolves the main backend window' => strpos($widget, 'currentSx.Window.getMainWindow()') !== false,
    'main window owns the telephony instance' => strpos($widget, 'mainSx.Telephony = new mainSx.classes.Telephony') !== false,
    'child windows only forward call buttons' => strpos($widget, ".off('click.sxTelephony', '.sx-telephony-btn')") !== false,
    'unconfigured users receive an informational notification in the active card' => strpos($widget, "Json::htmlEncode('Телефония не настроена. Обратитесь к администратору.')") !== false
        && preg_match('/if \(!\$telephonyUser\).*?currentSx\.notify\.info\(\{\$message\}\)/s', $widget) === 1,
    'company client and lead cards expose the shared call action' => strpos($companyView, 'sx-telephony-btn') !== false
        && strpos($clientView, 'sx-telephony-btn') !== false
        && strpos($leadView, 'sx-telephony-btn') !== false,
    'call panel sits above backend action windows' => strpos($styles, 'var(--sx-backend-window-z-index, 100000) + 10') !== false,
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "telephony-polling-contract: OK\n";
