<?php

$autoloadCandidates = [
    dirname(__DIR__).'/vendor/autoload.php',
    dirname(__DIR__, 3).'/autoload.php',
    '/app/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        break;
    }
}

use skeeks\cms\models\CmsDocumentTemplate;

$dark = CmsDocumentTemplate::preset(CmsDocumentTemplate::THEME_DARK);
$light = CmsDocumentTemplate::preset(CmsDocumentTemplate::THEME_LIGHT);

$checks = [
    'dark and light backgrounds differ' => $dark['background_color'] !== $light['background_color'],
    'dark cover is enabled' => $dark['show_cover'] === true,
    'light cover is enabled' => $light['show_cover'] === true,
    'dark orientation is valid' => isset(CmsDocumentTemplate::pageOrientations()[$dark['page_orientation']]),
    'light orientation is valid' => isset(CmsDocumentTemplate::pageOrientations()[$light['page_orientation']]),
    'task report type is registered' => isset(CmsDocumentTemplate::documentTypes()[CmsDocumentTemplate::DOCUMENT_TASK_REPORT]),
];

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$name}\n");
        exit(1);
    }
}

echo "cms-document-template-themes: OK\n";
