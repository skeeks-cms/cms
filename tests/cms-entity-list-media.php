<?php

$controllers = [
    'user'       => ['file' => 'AdminUserController.php', 'icon' => "'user'"],
    'company'    => ['file' => 'AdminCmsCompanyController.php', 'icon' => "'building'"],
    'deal'       => ['file' => 'AdminCmsDealController.php', 'icon' => "'handshake'"],
    'bill'       => ['file' => 'AdminCmsBillController.php', 'icon' => "'invoice'"],
    'contractor' => ['file' => 'AdminCmsContractorController.php', 'icon' => "'building'"],
    'document'   => ['file' => 'AdminCmsDocumentController.php', 'icon' => "'file'"],
];

function entityListMediaExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach ($controllers as $name => $config) {
    $source = file_get_contents(dirname(__DIR__).'/src/controllers/'.$config['file']);
    entityListMediaExpect(strpos($source, 'BackendEntityMedia::widget') !== false, ucfirst($name).' list has no canonical entity media.');
    entityListMediaExpect(strpos($source, $config['icon']) !== false, ucfirst($name).' list has no semantic fallback icon.');
    entityListMediaExpect(strpos($source, 'Image::getCapSrc()') === false, ucfirst($name).' list still uses the camera placeholder.');
}

echo "CMS entity list media contract: OK\n";
