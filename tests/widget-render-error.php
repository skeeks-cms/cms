<?php
define('YII_ENABLE_ERROR_HANDLER', false);
$vendor = (getenv('SKEEKS_APP_ROOT') ?: '/app').'/vendor';
require $vendor.'/autoload.php';
require $vendor.'/yiisoft/yii2/Yii.php';
$app = new yii\console\Application([
    'id' => 'widget-render-error-test', 'basePath' => __DIR__, 'vendorPath' => $vendor, 'extensions' => [],
    'language' => 'ru-RU',
    'components' => [
        'cache' => ['class' => yii\caching\ArrayCache::class],
        'i18n' => ['translations' => ['skeeks/*' => [
            'class' => yii\i18n\PhpMessageSource::class, 'sourceLanguage' => 'ru-RU',
        ]]],
    ],
]);
class FailingRenderableWidget extends skeeks\cms\base\WidgetRenderable
{
    public $failure;
    public $renders = 0;
    public function init() {}
    public function getCacheKey($is_user = true) { return 'render-error-test'; }
    public function getCacheDependency() { return null; }
    public function render($view, $params = [])
    {
        ++$this->renders;
        if ($this->failure) { throw $this->failure; }
        return 'recovered';
    }
}
foreach ([new RuntimeException('private SQL /var/www/site'), new Error('private PHP path')] as $failure) {
    $app->cache->flush();
    $widget = new FailingRenderableWidget(['is_cache' => true]);
    $widget->failure = $failure;
    $result = $widget->run();
    if (strpos($result, 'Не удалось загрузить блок') === false || strpos($result, 'private') !== false) {
        throw new RuntimeException('Failure details exposed');
    }
    $messages = Yii::getLogger()->messages;
    if (!array_filter($messages, static function ($message) use ($failure) { return $message[0] === $failure; })) {
        throw new RuntimeException('Failure missing from server log');
    }
    $widget->failure = null;
    if ($widget->run() !== 'recovered' || $widget->renders !== 2) {
        throw new RuntimeException('Transient failure was cached');
    }
    if ($widget->run() !== 'recovered' || $widget->renders !== 2) {
        throw new RuntimeException('Successful widget cache regressed');
    }
}
echo "PASS: exceptions and PHP errors stay private, are logged, are not cached; successful rendering is cached\n";
