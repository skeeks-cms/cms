<?php
// No site bootstrap or DB. Usage: php jfif-preview.php <vendor> <candidate Imaging.php>
define('YII_ENABLE_ERROR_HANDLER', false);
$vendor = $argv[1];
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';
require $argv[2];
use skeeks\cms\components\Imaging;
use skeeks\cms\components\imaging\filters\Thumbnail;
use skeeks\cms\controllers\ImagePreviewController;
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$root = sys_get_temp_dir() . '/skeeks-jfif-' . bin2hex(random_bytes(6));
mkdir($root . '/uploads', 0700, true);
$image = imagecreatetruecolor(128, 96);
imagejpeg($image, $root . '/uploads/source.jfif');
imagedestroy($image);
$app = new yii\web\Application([
    'id' => 'jfif-preview-smoke', 'basePath' => $root, 'vendorPath' => $vendor,
    'extensions' => [], 'bootstrap' => [],
    'components' => [
        'request' => ['class' => yii\web\Request::class, 'cookieValidationKey' => 'test', 'scriptFile' => $root . '/index.php', 'scriptUrl' => '/index.php'],
        'imaging' => ['class' => Imaging::class],
        'seo' => new class extends yii\base\Component { public $img_preview_quality = 90; },
    ],
]);
Yii::setAlias('@webroot', $root);
try {
    foreach ([false => 'jpg', true => 'webp'] as $webp => $output) {
        $url = $app->imaging->thumbnailUrlOnRequest('/uploads/source.jfif', new Thumbnail(['w'=>60, 'h'=>60, 'm'=>2]), 'news', (bool)$webp);
        check(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) === $output, 'Canonical output format');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        check($query['ext'] === 'jfif', 'Original extension must survive in signed params');
        $path = ltrim(parse_url($url, PHP_URL_PATH), '/');
        $app->request->setPathInfo($path);
        $app->request->setQueryParams($query);
        $app->set('response', new yii\web\Response());
        $response = (new ImagePreviewController('image-preview', $app))->actionProcess();
        $size = getimagesize($root . '/' . $path);
        check($size[0] === 60 && $size[1] === 60, 'Real preview must be 60x60');
        check($size['mime'] === ($webp ? 'image/webp' : 'image/jpeg'), 'Real output MIME');
        ob_start();
        $response->send();
        $body = ob_get_clean();
        check($body === file_get_contents($root . '/' . $path), 'First response bytes unchanged');
    }
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        $url = $app->imaging->thumbnailUrlOnRequest('/uploads/source.' . $ext, new Thumbnail(['w'=>60, 'h'=>60]), 'news', false);
        check(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) === $ext, 'Existing output extension preserved');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        check(!isset($query['ext']), 'Existing signature parameters preserved');
    }
    $url = $app->imaging->thumbnailUrlOnRequest('/uploads/source.svg', new Thumbnail(['w'=>60, 'h'=>60]), 'news', false);
    check($url === '/uploads/source.svg', 'Unsupported sources still bypass imaging');
    echo "JFIF JPEG/WebP generation, 60x60, first-response bytes and existing formats: OK\n";
} finally {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        if ($item->isDir() && !$item->isLink()) { rmdir($item->getPathname()); }
        else { unlink($item->getPathname()); }
    }
    rmdir($root);
}