<?php
/**
 * One broken notification must not break the header notification list:
 * getHtml() renders it without links and logs the error; records with only
 * an asText() method (plain Yii models of other packages) get their label.
 */
define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', false);
$vendor = (getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor';
require $vendor.'/autoload.php';
require $vendor.'/yiisoft/yii2/Yii.php';

class WebNotifyGuardSkeeks extends yii\base\Component
{
    public $modelsConfig = [];
}

$app = new yii\console\Application([
    'id' => 'web-notify-render-guard-test', 'basePath' => __DIR__, 'vendorPath' => $vendor, 'extensions' => [],
    'language' => 'ru-RU',
    'components' => [
        'db' => ['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
        'skeeks' => ['class' => WebNotifyGuardSkeeks::class],
        'i18n' => ['translations' => ['skeeks/*' => [
            'class' => yii\i18n\PhpMessageSource::class, 'sourceLanguage' => 'ru-RU',
        ]]],
    ],
]);
$app->db->createCommand()->createTable('cms_web_notify', [
    'id' => 'pk', 'created_at' => 'integer', 'name' => 'string', 'comment' => 'text', 'url' => 'string',
    'model_code' => 'string', 'model_id' => 'integer', 'cms_user_id' => 'integer', 'is_read' => 'integer',
])->execute();

class BrokenLabelModel extends yii\base\Model
{
    public function getAsText()
    {
        throw new RuntimeException('label failure');
    }
}

class MethodLabelModel extends yii\base\Model
{
    public function asText()
    {
        return 'Переписка с клиентом';
    }
}

class GuardWebNotify extends skeeks\cms\models\CmsWebNotify
{
    public $testModel;

    public function getModel()
    {
        return $this->testModel;
    }
}

function guardNotify($model, $url = null): GuardWebNotify
{
    $notify = new GuardWebNotify();
    $notify->setAttributes([
        'id' => 7, 'created_at' => time() - 60, 'name' => 'Вам назначена переписка', 'url' => $url,
        'model_code' => get_class($model), 'model_id' => 5, 'cms_user_id' => 1, 'is_read' => 0,
    ], false);
    $notify->testModel = $model;

    return $notify;
}

foreach ([null, '/~sxx/comm/chat?pk=5'] as $url) {
    Yii::getLogger()->messages = [];
    $html = guardNotify(new BrokenLabelModel(), $url)->getHtml();
    if (strpos($html, 'Вам назначена переписка') === false || strpos($html, '<a') !== false) {
        throw new RuntimeException('Broken notification is not rendered as plain text: '.$html);
    }
    if (strpos($html, 'label failure') !== false) {
        throw new RuntimeException('Failure details exposed in notification HTML');
    }
    $logged = array_filter(Yii::getLogger()->messages, static function ($message) {
        return $message[1] === yii\log\Logger::LEVEL_ERROR && strpos((string)$message[0], 'label failure') !== false;
    });
    if (!$logged) {
        throw new RuntimeException('Notification render failure missing from server log');
    }
}

$html = guardNotify(new MethodLabelModel(), '/~sxx/comm/chat?pk=5')->getHtml();
if (strpos($html, '>Переписка с клиентом</a>') === false) {
    throw new RuntimeException('asText() method label is not rendered: '.$html);
}

echo "CMS web notification render guard: ok\n";
