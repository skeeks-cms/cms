<?php

$root = dirname(__DIR__).'/src';
$model = file_get_contents($root.'/models/CmsWebNotify.php');
$migration = file_get_contents($root.'/migrations/m260820_130000__add_url_to_cms_web_notify.php');

foreach ([
    "[['url'], 'string', 'max' => 1000]",
    'public function getTargetUrl(): ?string',
    '$model instanceof CmsLead',
    'Html::a(Html::encode($model->asText), $url',
] as $fragment) {
    if (strpos($model, $fragment) === false) {
        throw new RuntimeException('Web notification link contract is incomplete: '.$fragment);
    }
}

if (strpos($migration, "'url'") === false || strpos($migration, 'string(1000)') === false) {
    throw new RuntimeException('Web notification URL migration is incomplete.');
}

echo "CMS web notification links: ok\n";
