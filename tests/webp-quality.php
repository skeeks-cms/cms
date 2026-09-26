<?php
// Isolated real-library smoke test; no site bootstrap or database connection.
define('YII_ENABLE_ERROR_HANDLER', false);
$vendor = $argv[1];
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';
require $argv[2]; // Candidate ImagePreviewController, before Composer loads it.
use skeeks\cms\components\imaging\filters\Thumbnail;
use skeeks\cms\components\Imaging;
use skeeks\cms\controllers\ImagePreviewController;

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$root = sys_get_temp_dir() . '/skeeks-webp-test-' . bin2hex(random_bytes(5));
mkdir($root . '/uploads', 0700, true);
copy($argv[3], $root . '/uploads/original.jpg');
$app = new yii\web\Application([
    'id' => 'webp-quality-smoke', 'basePath' => $root, 'vendorPath' => $vendor,
    'extensions' => [], 'bootstrap' => [],
    'components' => [
        'request' => ['class' => yii\web\Request::class, 'cookieValidationKey' => 'isolated-test', 'scriptFile' => $root . '/index.php', 'scriptUrl' => '/index.php'],
        'imaging' => ['class' => Imaging::class],
        'seo' => new class extends yii\base\Component { public $img_preview_quality = 90; },
    ],
]);
Yii::setAlias('@webroot', $root);
check(!isset((new Thumbnail(['w'=>900,'h'=>900,'m'=>1]))->config['q']), 'Default quality must not change URL parameters');
$rows = [];
foreach ([null, 30, 90] as $quality) {
    $params = ['w'=>900,'h'=>900,'m'=>1];
    if ($quality !== null) { $params['q'] = $quality; }
    $filter = new Thumbnail($params);
    $url = $app->imaging->thumbnailUrlOnRequest('/uploads/original.jpg', $filter, 'forum', true);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $path = ltrim(parse_url($url, PHP_URL_PATH), '/');
    $app->request->setPathInfo($path);
    $app->request->setQueryParams($query);
    $app->set('response', new yii\web\Response());
    $response = (new ImagePreviewController('image-preview', $app))->actionProcess();
    check($response instanceof yii\web\Response, 'Controller must return a response');
    ob_start();
    $response->send();
    $body = ob_get_clean();
    $saved = file_get_contents($root . '/' . $path);
    check($body === $saved, 'First response must match saved bytes');
    check($response->headers->get('Content-Type') === 'image/webp', 'WebP MIME type');
    check(strpos($response->headers->get('Content-Disposition'), 'inline;') === 0, 'Inline response');
    check(substr($body,8,4) === 'WEBP', 'Actual WebP encoding');
    $size = getimagesize($root . '/' . $path);
    check($size[0] === 900 && $size[1] === 900, 'Dimensions preserved');
    $rows[$quality === null ? 'default' : $quality] = ['bytes'=>strlen($body),'sha256'=>hash('sha256',$body)];
}
check($rows[30]['sha256'] !== $rows[90]['sha256'], 'WebP quality must affect encoded bytes');
check($rows[30]['bytes'] < $rows[90]['bytes'], 'Higher quality retains more data');
check($rows['default']['sha256'] === $rows[90]['sha256'], 'Site default 90 must equal explicit q=90');
foreach (['jpg' => 'image/jpeg', 'png' => 'image/png'] as $extension => $mime) {
    if ($extension === 'png') {
        $image = imagecreatefromjpeg($root . '/uploads/original.jpg');
        imagepng($image, $root . '/uploads/original.png');
        imagedestroy($image);
    }
    $url = $app->imaging->thumbnailUrlOnRequest('/uploads/original.' . $extension, new Thumbnail(['w'=>120,'h'=>120]), 'forum', false);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $path = ltrim(parse_url($url, PHP_URL_PATH), '/');
    $app->request->setPathInfo($path);
    $app->request->setQueryParams($query);
    $app->set('response', new yii\web\Response());
    $response = (new ImagePreviewController('image-preview', $app))->actionProcess();
    ob_start();
    $response->send();
    $body = ob_get_clean();
    check($body === file_get_contents($root . '/' . $path), $extension . ' exact bytes');
    check($response->headers->get('Content-Type') === $mime, $extension . ' MIME type');
}
echo json_encode(['ok'=>true,'driver'=>get_class(skeeks\imagine\Image::getImagine()),'results'=>$rows], JSON_PRETTY_PRINT) . "\n";
// Only remove fixtures created by this test.
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
    if ($item->isDir() && !$item->isLink()) { rmdir($item->getPathname()); } else { unlink($item->getPathname()); }
}
rmdir($root);
