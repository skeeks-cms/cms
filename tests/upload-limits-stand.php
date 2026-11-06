<?php
/**
 * Upload limits of the standard fields of sections and content elements (Cms settings «Загрузка фото и файлов»)
 * on the stand application, without database writes:
 * - defaults equal the former hardcoded values (sections 15/40/50/40, elements 15/100/50/50; MB and pieces);
 * - Tree / CmsContentElement rules take maxSize / maxFiles from \Yii::$app->cms->getUploadLimits();
 * - a chunk-assembled temporary file (a path string, as the uploader gives it) is checked by these limits;
 * - empty, zero or wrong stored values fall back to the defaults; narrower overrides win when set;
 * - Cms settings validation (MB > 0, fractional allowed; counts 1..1000);
 * - PropertyTypeStorageFile: maxSize/minSize stay bytes in saved settings, the form edits maxSizeMb/minSizeKb.
 *
 * Usage: UPLOAD_LIMITS_TEST=local php tests/upload-limits-stand.php
 * Optional UPLOAD_LIMITS_SRC=<dir with src/ of a working copy> loads changed classes before the application.
 */

if (getenv('UPLOAD_LIMITS_TEST') !== 'local') {
    throw new RuntimeException('Set UPLOAD_LIMITS_TEST=local.');
}
define('ROOT_DIR', getenv('TEST_APP_ROOT') ?: '/app');
define('YII_ENV', 'dev');
define('YII_DEBUG', true);
require ROOT_DIR.'/vendor/skeeks/cms/bootstrap.php';
if ($src = getenv('UPLOAD_LIMITS_SRC')) {
    foreach (['components/Cms.php', 'models/Tree.php', 'models/CmsContentElement.php', 'relatedProperties/propertyTypes/PropertyTypeStorageFile.php'] as $file) {
        require_once rtrim($src, '/').'/src/'.$file;
    }
}
$config = new Yiisoft\Config\Config(new Yiisoft\Config\ConfigPaths(ROOT_DIR, 'config'), null, [Yiisoft\Config\Modifier\RecursiveMerge::groups('web', 'web-'.ENV, 'params', 'params-web-'.ENV)], 'params-web-'.ENV);
$c = $config->get($config->has('web-'.ENV) ? 'web-'.ENV : 'web');
$c['components']['request']['scriptFile'] = ROOT_DIR.'/frontend/web/index.php';
$c['components']['request']['scriptUrl'] = '/index.php';
$c['components']['request']['hostInfo'] = 'http://localhost';
$c['components']['user']['enableSession'] = false;
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\Tree;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeStorageFile;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};
$mb = 1048576;

$cms = Yii::$app->cms;
$attributes = ['tree_image_max_size_mb', 'tree_images_max_count', 'tree_file_max_size_mb', 'tree_files_max_count',
    'element_image_max_size_mb', 'element_images_max_count', 'element_file_max_size_mb', 'element_files_max_count'];
$saved = [];
foreach ($attributes as $attribute) {
    $saved[$attribute] = $cms->$attribute;
}

$tmp = sys_get_temp_dir().'/upload-limits-'.uniqid();
mkdir($tmp);
$makeFile = static function (string $name, int $bytes) use ($tmp) {
    $path = $tmp.'/'.$name;
    $fh = fopen($path, 'wb');
    $left = $bytes;
    while ($left > 0) {
        $part = min($left, 65536);
        fwrite($fh, str_repeat('a', $part));
        $left -= $part;
    }
    fclose($fh);
    return $path;
};

try {
    // Defaults equal the former hardcoded rules.
    $check(Cms::defaultUploadLimits(Cms::UPLOAD_SCOPE_TREE) === ['image_max_size' => 15 * $mb, 'images_max_count' => 40, 'file_max_size' => 50 * $mb, 'files_max_count' => 40], 'section defaults');
    $check(Cms::defaultUploadLimits(Cms::UPLOAD_SCOPE_ELEMENT) === ['image_max_size' => 15 * $mb, 'images_max_count' => 100, 'file_max_size' => 50 * $mb, 'files_max_count' => 50], 'element defaults');
    $fresh = new Cms();
    foreach (Cms::defaultUploadLimitSettings() as $scope => $values) {
        foreach ($values as $key => $value) {
            $check($fresh->{$scope.'_'.$key} == $value, "class default {$scope}_{$key}");
        }
    }

    // Rules read the component.
    $cms->element_file_max_size_mb = 1;
    $cms->element_files_max_count = 2;
    $cms->element_image_max_size_mb = 0.5;
    $cms->tree_file_max_size_mb = 3;
    $cms->tree_images_max_count = 7;

    $rule = static function ($model, string $attribute) {
        foreach ($model->getActiveValidators($attribute) as $validator) {
            if ($validator instanceof \yii\validators\FileValidator) {
                return $validator;
            }
        }
        return null;
    };
    $element = new CmsContentElement();
    $check($rule($element, 'fileIds')->maxSize === 1 * $mb && $rule($element, 'fileIds')->maxFiles === 2, 'element fileIds rule from settings');
    $check($rule($element, 'image_id')->maxSize === (int)round(0.5 * $mb), 'element image rule, fractional MB');
    $check($rule($element, 'imageIds')->maxFiles === 100, 'element imageIds count stays default');
    $tree = new Tree();
    $check($rule($tree, 'fileIds')->maxSize === 3 * $mb && $rule($tree, 'fileIds')->maxFiles === 40, 'section fileIds rule from settings');
    $check($rule($tree, 'imageIds')->maxFiles === 7 && $rule($tree, 'imageIds')->maxSize === 15 * $mb, 'section imageIds rule from settings');

    // A temporary file assembled from chunks is validated by the configured size.
    $big = $makeFile('big.pdf', (int)(1.5 * $mb));
    $small = $makeFile('small.pdf', 300 * 1024);
    $element = new CmsContentElement();
    $element->fileIds = [$big];
    $check(!$element->validate(['fileIds']) && $element->hasErrors('fileIds'), 'element: 1.5 MB file is too big for 1 MB');
    $element = new CmsContentElement();
    $element->fileIds = [$small, $small, $small];
    $check(!$element->validate(['fileIds']), 'element: 3 files over the limit of 2');
    $element = new CmsContentElement();
    $element->fileIds = [$small, $small];
    $check($element->validate(['fileIds']), 'element: 2 small files pass '.implode(' ', $element->getErrorSummary(true)));
    $tree = new Tree();
    $tree->fileIds = [$big];
    $check($tree->validate(['fileIds']), 'section: 1.5 MB file fits 3 MB '.implode(' ', $tree->getErrorSummary(true)));

    // Raising the element file limit to 100 MB (the idm-plus.ru case).
    $cms->element_file_max_size_mb = 100;
    $element = new CmsContentElement();
    $check($rule($element, 'fileIds')->maxSize === 100 * $mb, 'element files up to 100 MB');
    $element->fileIds = [$big];
    $check($element->validate(['fileIds']), 'element: 1.5 MB file fits 100 MB');

    // Fallback and overrides.
    $cms->element_file_max_size_mb = 0;
    $cms->element_files_max_count = '';
    $limits = $cms->getUploadLimits(Cms::UPLOAD_SCOPE_ELEMENT);
    $check($limits['file_max_size'] === 50 * $mb && $limits['files_max_count'] === 50, 'empty or zero stored values fall back to defaults');
    $limits = $cms->getUploadLimits(Cms::UPLOAD_SCOPE_TREE, ['file_max_size_mb' => 200, 'images_max_count' => '', 'image_max_size_mb' => 0]);
    $check($limits['file_max_size'] === 200 * $mb && $limits['images_max_count'] === 7 && $limits['image_max_size'] === 15 * $mb, 'a narrower override wins only when set');
    $thrown = false;
    try {
        $cms->getUploadLimits('unknown');
    } catch (\yii\base\InvalidArgumentException $e) {
        $thrown = true;
    }
    $check($thrown, 'unknown scope is refused');

    // Settings validation.
    $form = new Cms();
    $form->element_file_max_size_mb = '0.5';
    $form->tree_files_max_count = 10;
    $check($form->validate($attributes), 'valid settings: '.implode(' ', $form->getErrorSummary(true)));
    $form->element_file_max_size_mb = '0';
    $form->tree_files_max_count = 0;
    $check(!$form->validate($attributes) && $form->hasErrors('element_file_max_size_mb') && $form->hasErrors('tree_files_max_count'), 'zero size and count are refused');
    $form->element_file_max_size_mb = '';
    $check(!$form->validate(['element_file_max_size_mb']), 'empty size is refused');
    $check(isset($form->getConfigFormFields()['uploads']['fields']['element_file_max_size_mb']), 'settings form has the uploads fieldset');

    // PropertyTypeStorageFile: bytes in storage, MB/KB in the form.
    $handler = new PropertyTypeStorageFile();
    $handler->load(['maxSize' => 10485760, 'minSize' => 1024], '');
    $check($handler->maxSizeMb == 10 && $handler->minSizeKb == 1, 'saved bytes are shown as 10 MB and 1 KB');
    $handler->load(['PropertyTypeStorageFile' => ['maxSizeMb' => '100', 'minSizeKb' => '0.5']]);
    $check($handler->maxSize === 100 * $mb && $handler->minSize === 512, 'form values are stored as bytes');
    $stored = $handler->toArray();
    $check($stored['maxSize'] === 100 * $mb && !array_key_exists('maxSizeMb', $stored) && !array_key_exists('minSizeKb', $stored), 'component_settings keep bytes only');
    $handler->maxSizeMb = '0';
    $check(!$handler->validate(['maxSizeMb']), 'zero MB is refused');
    $check(strpos($handler->attributeHints()['maxSizeMb'], 'мегабайт') !== false && !isset($handler->attributeHints()['maxSize']), 'hint speaks of megabytes');
} finally {
    foreach ($saved as $attribute => $value) {
        $cms->$attribute = $value;
    }
    foreach (glob($tmp.'/*') as $file) {
        unlink($file);
    }
    rmdir($tmp);
}

echo "OK {$checks} checks\n";
