<?php

function cmsUpaSupportExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$controller = file_get_contents($root.'/src/controllers/UpaSupportController.php');
$view = file_get_contents($root.'/src/views/upa-support/view.php');
$asset = file_get_contents($root.'/src/assets/CmsUpaSupportAsset.php');

cmsUpaSupportExpect(is_file($root.'/src/support/SupportTaskExecutorResolver.php'), 'CMS misses the customer support executor resolver.');
cmsUpaSupportExpect(strpos($controller, 'namespace skeeks\\cms\\controllers;') !== false, 'Customer support controller is not CMS-owned.');
cmsUpaSupportExpect(strpos($controller, '/cms/upa-support/view') !== false, 'Customer task links do not use the CMS route.');
cmsUpaSupportExpect(strpos($controller, '@skeeks/cms/views/upa-support/view') !== false, 'Customer support does not use the CMS view.');
cmsUpaSupportExpect(strpos($controller, 'skeeks\\hosting') === false, 'CMS customer support still depends on cms-hosting.');
cmsUpaSupportExpect(strpos($view, 'CmsUpaSupportAsset::register') !== false, 'Customer support view misses its CMS asset.');
cmsUpaSupportExpect(strpos($asset, "@skeeks/cms/assets/src") !== false, 'Customer support asset is not CMS-owned.');

echo "CMS UPA support contract: OK\n";
