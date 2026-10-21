<?php
/**
 * Extension points used by communication packages: log renderers, activity
 * feed log types, phone/email row actions of CRM cards and lead sources.
 *
 * Usage: php tests/crm-extension-points.php [vendor/autoload.php]
 */

$autoloadCandidates = array_filter([
    $argv[1] ?? null,
    dirname(__DIR__).'/vendor/autoload.php',
    dirname(__DIR__, 3).'/autoload.php',
    '/app/vendor/autoload.php',
]);
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        require_once dirname($autoload).'/yiisoft/yii2/Yii.php';
        break;
    }
}

use skeeks\cms\log\CmsLogRendererInterface;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\Skeeks;
use skeeks\cms\widgets\admin\CrmContactActionsEvent;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\base\Event;

function crmExtensionExpect($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class CrmExtensionTestRenderer implements CmsLogRendererInterface
{
    public function render(CmsLog $log): string
    {
        return 'message #'.(int)($log->data['id'] ?? 0);
    }
}

/** CmsLog without a database schema: only the attributes render() reads. */
class CrmExtensionTestLog extends CmsLog
{
    public function attributes()
    {
        return ['log_type', 'data', 'comment', 'model_code', 'model_id', 'model_as_text'];
    }
}

new \yii\console\Application([
    'id' => 'crm-extension-points',
    'basePath' => sys_get_temp_dir(),
    'components' => [
        'skeeks' => [
            'class' => Skeeks::class,
            'logRenderers' => [
                'message' => CrmExtensionTestRenderer::class,
                'note' => static fn(CmsLog $log) => 'note: '.$log->comment,
                'broken' => \stdClass::class,
            ],
            'activityLogTypes' => ['message', CmsLog::LOG_TYPE_COMMENT],
        ],
    ],
]);

$skeeks = Yii::$app->skeeks;

// Activity feed types: defaults first, package types added once.
crmExtensionExpect($skeeks->activityLogTypes === [CmsLog::LOG_TYPE_PHONE_CALL, CmsLog::LOG_TYPE_COMMENT, 'message'],
    'Activity log types must keep calls and comments and add package types without duplicates.');
$skeeks->activityLogTypes = ['message', 'note'];
crmExtensionExpect($skeeks->activityLogTypes === [CmsLog::LOG_TYPE_PHONE_CALL, CmsLog::LOG_TYPE_COMMENT, 'message', 'note'],
    'A later registration must add to, not replace, the activity log types.');
$query = CmsLog::find()->activity();
crmExtensionExpect($query->where === ['log_type' => $skeeks->activityLogTypes], 'CmsLogQuery::activity() must filter by the registered types.');

// Renderers.
crmExtensionExpect($skeeks->getLogRenderer(CmsLog::LOG_TYPE_COMMENT) === null, 'Built-in types without a renderer must keep the built-in rendering.');
crmExtensionExpect($skeeks->getLogRenderer('message') === $skeeks->getLogRenderer('message'), 'A renderer must be created once.');

$log = new CrmExtensionTestLog();
$log->log_type = 'message';
$log->data = ['id' => 42];
crmExtensionExpect($log->render() === 'message #42', 'CmsLog::render() must delegate to a registered renderer class.');

$log = new CrmExtensionTestLog();
$log->log_type = 'note';
$log->comment = 'hello';
crmExtensionExpect($log->render() === 'note: hello', 'CmsLog::render() must accept a callable renderer.');

$log = new CrmExtensionTestLog();
$log->log_type = CmsLog::LOG_TYPE_COMMENT;
$log->comment = 'plain comment';
crmExtensionExpect($log->render() === 'plain comment', 'Comments must render as before.');

try {
    $skeeks->getLogRenderer('broken');
    crmExtensionExpect(false, 'A renderer without the interface must be rejected.');
} catch (\yii\base\InvalidConfigException $e) {
}

// Contact row actions: defaults, then package handlers.
$defaults = ['sms' => '<b>sms</b>', 'call' => '<b>call</b>'];
crmExtensionExpect(CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_PHONE, '+7 900', null, $defaults) === '<b>sms</b><b>call</b>',
    'Without handlers the card defaults must render unchanged and in order.');

$seen = null;
$handler = static function (CrmContactActionsEvent $event) use (&$seen) {
    $seen = [$event->contactType, $event->value, $event->owner];
    if ($event->contactType === CrmContactBlockWidget::CONTACT_EMAIL) {
        $event->actions['mailto'] = '<b>compose</b>';
        $event->actions['history'] = '<b>history</b>';
    }
};
Event::on(CrmContactBlockWidget::class, CrmContactBlockWidget::EVENT_CONTACT_ACTIONS, $handler);
$owner = new \yii\base\Model();
crmExtensionExpect(CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_EMAIL, 'a@b.ru', $owner, ['mailto' => '<b>mailto</b>']) === '<b>compose</b><b>history</b>',
    'A handler must be able to replace a default action in place and add new ones.');
crmExtensionExpect($seen === [CrmContactBlockWidget::CONTACT_EMAIL, 'a@b.ru', $owner], 'The event must carry the contact type, value and card record.');
crmExtensionExpect(CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_PHONE, '+7 900', $owner, $defaults) === '<b>sms</b><b>call</b>',
    'An email handler must not change phone rows.');
Event::off(CrmContactBlockWidget::class, CrmContactBlockWidget::EVENT_CONTACT_ACTIONS, $handler);

// Cards render rows through the event.
foreach (['admin-cms-lead', 'admin-cms-company', 'admin-user'] as $viewDir) {
    $view = file_get_contents(dirname(__DIR__).'/src/views/'.$viewDir.'/view.php');
    crmExtensionExpect(substr_count($view, 'CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_') === 2,
        $viewDir.' must render phone and email actions through CrmContactBlockWidget::contactActions().');
    crmExtensionExpect(strpos($view, 'Logs()->activity()') !== false, $viewDir.' activity feed must use CmsLogQuery::activity().');
}

// Lead sources of communication channels.
crmExtensionExpect(isset(CmsLead::sources()[CmsLead::SOURCE_EMAIL], CmsLead::sources()[CmsLead::SOURCE_MAX]),
    'Lead sources must include email and MAX.');

echo "CRM extension points: OK\n";
