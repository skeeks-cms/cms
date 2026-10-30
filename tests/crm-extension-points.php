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

/** A record created from a test source: one SQLite table. */
class CrmExtensionTestRecord extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'test_record';
    }

    public function rules()
    {
        return [[['name', 'owner'], 'safe']];
    }
}

/** "note:<id>": a source the test owns; id 7 exists, id 8 is hidden from the employee. */
class CrmExtensionTestSource implements \skeeks\cms\source\RecordSourceInterface, \skeeks\cms\source\RecordOriginInterface
{
    public function origins(\yii\db\ActiveRecord $record): array
    {
        return $record->owner === 'acme' ? [['label' => 'Создано из <заметки>', 'html' => '<a href="#n7">№7</a>'], ['label' => 'пусто', 'html' => '']] : [];
    }

    public static array $created = [];
    public static bool $failCreated = false;

    public function find(string $id)
    {
        return $id === '7' ? (object)['id' => 7, 'title' => 'Письмо «Привет»', 'owner' => 'acme'] : null;
    }

    public function supports(string $modelClass): bool
    {
        return $modelClass === CrmExtensionTestRecord::class;
    }

    public function prefill($source, \yii\db\ActiveRecord $model): void
    {
        $model->name = $source->title;
    }

    public function enforce($source, \yii\db\ActiveRecord $model): void
    {
        $model->owner = $source->owner;
    }

    public function created($source, \yii\db\ActiveRecord $model): void
    {
        if (self::$failCreated) {
            throw new \RuntimeException('link failed');
        }
        self::$created[] = [$source->id, (int)$model->id];
    }

    public function describe($source): string
    {
        return 'Из '.$source->title;
    }
}

/** What RecordSources reads of a create action: its new model and its fields. */
class CrmExtensionTestCreateAction extends \yii\base\Component
{
    public $model;
    public $fields;
}

new \yii\console\Application([
    'id' => 'crm-extension-points',
    'basePath' => sys_get_temp_dir(),
    'components' => [
        'db' => ['class' => \yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
        'skeeks' => [
            'recordSources' => [
                'note' => CrmExtensionTestSource::class,
                'broken' => \stdClass::class,
            ],
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

// Cards render rows through the event: directly (the request data of a deal) or through the shared rows
// phoneItems()/emailItems() of the company and client cards and of «Клиент» in the deal card (П2г).
$widgetSource = file_get_contents(dirname(__DIR__).'/src/widgets/admin/CrmContactBlockWidget.php');
crmExtensionExpect(substr_count($widgetSource, 'self::contactActions(self::CONTACT_') === 2,
    'phoneItems() and emailItems() must render their actions through contactActions().');
$sharedRows = static fn(string $source): bool => strpos($source, 'CrmContactBlockWidget::phoneItems(') !== false
    && strpos($source, 'CrmContactBlockWidget::emailItems(') !== false;
crmExtensionExpect($sharedRows(file_get_contents(dirname(__DIR__).'/src/views/admin-cms-lead/_client.php')),
    '«Клиент» of the deal card must use the rows of the company and client cards.');
foreach (['admin-cms-lead', 'admin-cms-company', 'admin-user'] as $viewDir) {
    $view = file_get_contents(dirname(__DIR__).'/src/views/'.$viewDir.'/view.php');
    crmExtensionExpect(substr_count($view, 'CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_') === 2 || $sharedRows($view),
        $viewDir.' must render phone and email actions through CrmContactBlockWidget::contactActions().');
    crmExtensionExpect(strpos($view, 'Logs()->activity()') !== false, $viewDir.' activity feed must use CmsLogQuery::activity().');
}

// Lead sources of communication channels.
crmExtensionExpect(isset(CmsLead::sources()[CmsLead::SOURCE_EMAIL], CmsLead::sources()[CmsLead::SOURCE_MAX]),
    'Lead sources must include email and MAX.');

// "Создание из источника": ?source=<type>:<id> of a standard create window.
use skeeks\cms\source\RecordSources;

crmExtensionExpect(RecordSources::parse('note:7') === ['note', '7'], 'A "<type>:<id>" value must parse.');
foreach (['', 'note', 'note:', ':7', 'Note:7', 'note:7;drop', ['note:7'], null] as $bad) {
    crmExtensionExpect(RecordSources::parse($bad) === null, 'A malformed source must be ignored: '.json_encode($bad));
}
crmExtensionExpect($skeeks->getRecordSource('note') === $skeeks->getRecordSource('note'), 'A record source must be created once.');
crmExtensionExpect($skeeks->getRecordSource('unknown') === null, 'An unknown source type must give null.');
try {
    $skeeks->getRecordSource('broken');
    crmExtensionExpect(false, 'A record source without the interface must be rejected.');
} catch (\yii\base\InvalidConfigException $e) {
}
crmExtensionExpect(RecordSources::resolve('note:8', CrmExtensionTestRecord::class) === null, 'A source hidden from the employee must be ignored.');
crmExtensionExpect(RecordSources::resolve('note:7', \yii\base\Model::class) === null, 'A source that does not support the model must be ignored.');
crmExtensionExpect(RecordSources::resolve('other:7', CrmExtensionTestRecord::class) === null, 'An unregistered source type must be ignored.');

Yii::$app->db->createCommand()->createTable('test_record', ['id' => 'pk', 'name' => 'string', 'owner' => 'string'])->execute();
$createAction = static function () {
    $action = new CrmExtensionTestCreateAction(['model' => new CrmExtensionTestRecord(), 'fields' => static fn() => ['name' => []]]);
    return $action;
};
$run = static function (CrmExtensionTestCreateAction $action, array $post) {
    $action->model->setAttributes($post);
    RecordSources::onBeforeValidate(new Event(['sender' => $action]));
    RecordSources::onBeforeSave(new Event(['sender' => $action]));
    $action->model->save(false);
    RecordSources::onAfterSave(new Event(['sender' => $action]));
};

$action = $createAction();
crmExtensionExpect(!RecordSources::start($action, 'note:8'), 'A hidden source must leave the window as it is.');
crmExtensionExpect($action->model->name === null && is_callable($action->fields) && call_user_func($action->fields, $action) === ['name' => []],
    'Without a source the record and its fields must stay unchanged.');

$action = $createAction();
crmExtensionExpect(RecordSources::start($action, 'note:7'), 'A visible source must be applied.');
crmExtensionExpect($action->model->name === 'Письмо «Привет»', 'prefill() must fill the new record.');
$fields = call_user_func($action->fields, $action);
crmExtensionExpect(array_keys($fields) === ['__record_source', 'name'] && strpos($fields['__record_source']['content'], 'Из Письмо «Привет»') !== false,
    'The source line must stand above the original fields, encoded.');
// The employee edits the name; the owner is the source's and comes back before validation.
$run($action, ['name' => 'Своё название', 'owner' => 'forged']);
$saved = CrmExtensionTestRecord::find()->orderBy(['id' => SORT_DESC])->one();
crmExtensionExpect($saved->name === 'Своё название' && $saved->owner === 'acme', 'Posted values must win over prefill(); enforce() must set the source attributes.');
crmExtensionExpect(CrmExtensionTestSource::$created === [[7, (int)$saved->id]], 'created() must run once with the saved record.');

// A failing link rolls the new record back.
CrmExtensionTestSource::$failCreated = true;
$action = $createAction();
RecordSources::start($action, 'note:7');
$count = (int)CrmExtensionTestRecord::find()->count();
try {
    $run($action, ['name' => 'x']);
    crmExtensionExpect(false, 'A failing created() must be reported.');
} catch (\RuntimeException $e) {
    crmExtensionExpect($e->getMessage() === 'link failed', 'The error of created() must reach the window.');
}
crmExtensionExpect((int)CrmExtensionTestRecord::find()->count() === $count && !Yii::$app->db->getTransaction(),
    'A failing created() must roll the record back and close the transaction.');
CrmExtensionTestSource::$failCreated = false;

// A window without a source does not open a transaction.
$action = $createAction();
RecordSources::start($action, null);
$run($action, ['name' => 'plain']);
crmExtensionExpect(!Yii::$app->db->getTransaction() && count(CrmExtensionTestSource::$created) === 1, 'A window without a source must save as before.');

// "Создано из …" at the top of the card: sources that know the origin of a saved record.
use skeeks\cms\source\RecordOriginWidget;

$fromSource = CrmExtensionTestRecord::find()->andWhere(['owner' => 'acme'])->one();
$plain = CrmExtensionTestRecord::find()->andWhere(['name' => 'plain'])->one();
crmExtensionExpect(RecordOriginWidget::origins($fromSource) === [['label' => 'Создано из <заметки>', 'html' => '<a href="#n7">№7</a>']],
    'Origins must come from sources implementing RecordOriginInterface, without empty entries.');
crmExtensionExpect(RecordOriginWidget::origins($plain) === [] && RecordOriginWidget::origins(new CrmExtensionTestRecord()) === [],
    'A record without an origin and a new record must have none.');
foreach (['admin-cms-task', 'admin-cms-lead', 'admin-cms-deal'] as $viewDir) {
    $view = file_get_contents(dirname(__DIR__).'/src/views/'.$viewDir.'/view.php');
    crmExtensionExpect(strpos($view, '\skeeks\cms\source\RecordOriginWidget::widget([\'model\' => $model') !== false, $viewDir.' must show the origin of the record.');
}

// The task card shows the registered feed types (letters linked to the task), not only comments.
$taskView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-task/view.php');
// The feed is the activity, the result (pinned) once under the block of actions and not again in the feed.
crmExtensionExpect(strpos($taskView, "'query'                => \$model->getLogs()->activity()->notPinned(),") !== false
    && strpos($taskView, "\$model->getLogs()->activity()->pinned()") !== false, 'The task feed must use CmsLogQuery::activity(), its result pinned once.');

// Blocks of other packages in lead, company and deal cards (proposals first).
$cardOwner = new CrmExtensionTestRecord();
crmExtensionExpect(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::blocks($cardOwner) === [], 'A card without handlers has no package blocks.');
$blocksHandler = static function (\skeeks\cms\widgets\admin\CrmCardBlocksEvent $e) {
    if ($e->position !== \skeeks\cms\widgets\admin\CrmCardBlocksWidget::POSITION_ASIDE) {
        return;
    }
    $e->blocks['bills'] = '<b>bills</b>';
    $e->blocks['empty'] = '  ';
    $e->blocks['proposals'] = '<b>proposals</b>';
    $e->priority['proposals'] = 50;
};
Event::on(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::class, \skeeks\cms\widgets\admin\CrmCardBlocksWidget::EVENT_BLOCKS, $blocksHandler);
crmExtensionExpect(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::blocks($cardOwner) === ['proposals' => '<b>proposals</b>', 'bills' => '<b>bills</b>'],
    'Blocks are ordered by priority, then by the order handlers added them; empty blocks are dropped.');
crmExtensionExpect(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::blocks($cardOwner, \skeeks\cms\widgets\admin\CrmCardBlocksWidget::POSITION_MAIN) === [], 'Handlers see the position of the place.');
crmExtensionExpect(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::blocks(null) === [], 'No record, no blocks.');
Event::off(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::class, \skeeks\cms\widgets\admin\CrmCardBlocksWidget::EVENT_BLOCKS, $blocksHandler);
foreach (['admin-cms-lead', 'admin-cms-company', 'admin-cms-deal', 'admin-user'] as $viewDir) {
    $view = file_get_contents(dirname(__DIR__).'/src/views/'.$viewDir.'/view.php');
    crmExtensionExpect(strpos($view, 'CrmCardBlocksWidget::') !== false, $viewDir.' must render package blocks through CrmCardBlocksWidget.');
}

// Actions of packages in the next step of a card ("Отправить презентацию", "Сделать КП").
crmExtensionExpect(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::actions($cardOwner) === [], 'A card without handlers has no package actions.');
$actionsHandler = static function (\skeeks\cms\widgets\admin\CrmCardBlocksEvent $e) {
    $e->actions['proposal'] = ['label' => 'Сделать КП', 'url' => '/kp'];
    $e->priority['proposal'] = 60;
    $e->actions['presentation'] = ['label' => 'Отправить презентацию', 'url' => '/presentations', 'icon' => 'send'];
    $e->priority['presentation'] = 50;
    $e->actions['broken'] = ['label' => 'Без адреса'];
};
Event::on(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::class, \skeeks\cms\widgets\admin\CrmCardBlocksWidget::EVENT_ACTIONS, $actionsHandler);
crmExtensionExpect(array_keys(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::actions($cardOwner)) === ['presentation', 'proposal'],
    'Actions are ordered by priority; an action without a label or an address is dropped.');
crmExtensionExpect(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::actions(null) === [], 'No record, no actions.');
Event::off(\skeeks\cms\widgets\admin\CrmCardBlocksWidget::class, \skeeks\cms\widgets\admin\CrmCardBlocksWidget::EVENT_ACTIONS, $actionsHandler);
// The block of actions over the feed takes the tabs of packages; deal, company and client cards connect it.
crmExtensionExpect(strpos(file_get_contents(dirname(__DIR__).'/src/widgets/admin/CrmCardActionsWidget.php'), 'CrmCardBlocksWidget::actions(') !== false,
    'The block of actions of a card offers the actions of packages.');
foreach (['admin-cms-lead/view.php', 'admin-cms-company/view.php', 'admin-user/view.php'] as $cardView) {
    $cardSource = file_get_contents(dirname(__DIR__).'/src/views/'.$cardView);
    crmExtensionExpect(strpos($cardSource, 'CrmCardActionsWidget::widget(') !== false || strpos($cardSource, 'CrmCardActionsWidget::client(') !== false,
        'The card '.$cardView.' shows the block of actions.');
}

// «Каналы связи» of a client person: core emails and phones, channels and notes of packages, order by priority.
\Yii::$app->db->createCommand('CREATE TABLE IF NOT EXISTS cms_user (id INTEGER PRIMARY KEY, is_worker INTEGER DEFAULT 0)')->execute();
$channelPerson = new \skeeks\cms\models\CmsUser();
$channelPerson->id = 9001;
$channelPerson->populateRelation('cmsUserEmails', [new class(['value' => 'Ivan@Site.ru', 'is_approved' => 1]) extends \yii\base\Model {
    public $value; public $is_approved;
}]);
$channelPerson->populateRelation('cmsUserPhones', [new class(['value' => '+7 (999) 111-22-33', 'is_approved' => 0]) extends \yii\base\Model {
    public $value; public $is_approved;
}]);
$channelsHandler = static function (\skeeks\cms\widgets\admin\ClientChannelsEvent $e) {
    $e->channels['email:ivan@site.ru']['notes'][] = 'отписан от рекламы';
    $e->channels['bot:max'] = ['kind' => 'messenger', 'icon' => 'chat', 'label' => 'MAX', 'state' => 'off', 'notes' => ['не привязан'], 'priority' => 300];
    $e->channels['app'] = ['kind' => 'app', 'label' => 'Приложение', 'state' => 'ok', 'priority' => 150];
    $e->channels['broken'] = ['kind' => 'x'];
};
Event::on(\skeeks\cms\widgets\admin\ClientChannelsWidget::class, \skeeks\cms\widgets\admin\ClientChannelsWidget::EVENT_CHANNELS, $channelsHandler);
$channels = \skeeks\cms\widgets\admin\ClientChannelsWidget::channels($channelPerson);
Event::off(\skeeks\cms\widgets\admin\ClientChannelsWidget::class, \skeeks\cms\widgets\admin\ClientChannelsWidget::EVENT_CHANNELS, $channelsHandler);
crmExtensionExpect(array_keys($channels) === ['email:ivan@site.ru', 'app', 'phone:79991112233', 'bot:max'],
    'Channels: core email and phone keys, package channels by priority, a channel without a label is dropped.');
crmExtensionExpect($channels['email:ivan@site.ru']['notes'] === ['подтверждён', 'отписан от рекламы'], 'A package adds notes to a core email.');
crmExtensionExpect($channels['phone:79991112233']['notes'] === ['не подтверждён'], 'The phone says it is not confirmed.');
crmExtensionExpect(\skeeks\cms\widgets\admin\ClientChannelsWidget::describe($channels['email:ivan@site.ru']) === 'Email Ivan@Site.ru: подтверждён, отписан от рекламы',
    'A channel is described in one line for tooltips.');

// The place of client subscriptions in «Доступ и уведомления»: empty without handlers, filled by a package.
\Yii::$app->db->createCommand('CREATE TABLE IF NOT EXISTS cms_deal (id INTEGER PRIMARY KEY, name TEXT)')->execute();
$notifyDeal = new \skeeks\cms\models\CmsDeal();
$notify = \skeeks\cms\widgets\admin\DealAccessNotifyEvent::collect($notifyDeal, [5 => []]);
crmExtensionExpect(!$notify->handled && $notify->cells === [], 'Without a notification package the block shows only the note.');
$notifyHandler = static function (\skeeks\cms\widgets\admin\DealAccessNotifyEvent $e) {
    foreach (array_keys($e->people) as $userId) {
        $e->cells[$userId] = 'счета, задачи';
    }
};
Event::on(\skeeks\cms\widgets\admin\DealAccessNotifyEvent::class, \skeeks\cms\widgets\admin\DealAccessNotifyEvent::EVENT_NOTIFY, $notifyHandler);
$notify = \skeeks\cms\widgets\admin\DealAccessNotifyEvent::collect($notifyDeal, [5 => []]);
Event::off(\skeeks\cms\widgets\admin\DealAccessNotifyEvent::class, \skeeks\cms\widgets\admin\DealAccessNotifyEvent::EVENT_NOTIFY, $notifyHandler);
crmExtensionExpect($notify->handled && $notify->cells === [5 => 'счета, задачи'], 'A notification package fills the column per person.');
$dealView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-deal/_client-access.php');
crmExtensionExpect(strpos($dealView, 'DealAccessNotifyEvent::collect(') !== false && strpos($dealView, "ClientChannelsWidget::widget(['user' => \$person['user'], 'compact' => true])") !== false,
    'The deal card asks packages for subscriptions and shows channels of participants.');
crmExtensionExpect(strpos(file_get_contents(dirname(__DIR__).'/src/views/admin-user/view.php'), 'ClientChannelsWidget::widget(') !== false,
    'The client card shows «Каналы связи».');

echo "CRM extension points: OK\n";
