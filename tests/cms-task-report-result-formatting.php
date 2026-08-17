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

use skeeks\cms\controllers\AdminCmsTaskController;

$reflection = new ReflectionClass(AdminCmsTaskController::class);
$controller = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('normalizeTaskReportResultComment');
$method->setAccessible(true);

$input = '<p>Первая строка</p><div>Вторая<br>Третья</div><ul><li>Пункт 1</li><li>Пункт 2</li></ul>';
$expected = "Первая строка\nВторая\nТретья\n• Пункт 1\n• Пункт 2";
$actual = $method->invoke($controller, $input);

if ($actual !== $expected) {
    fwrite(STDERR, "FAILED: task report result formatting\n");
    fwrite(STDERR, "Expected: ".json_encode($expected, JSON_UNESCAPED_UNICODE)."\n");
    fwrite(STDERR, "Actual:   ".json_encode($actual, JSON_UNESCAPED_UNICODE)."\n");
    exit(1);
}

// Editor block markup must not create blank rows in reports.
$inputWithEditorWhitespace = "<p>Строка 1</p>\n\n<p>Строка 2</p>\n<div>Строка 3<br>Строка 4</div>";
$expectedWithEditorWhitespace = "Строка 1\nСтрока 2\nСтрока 3\nСтрока 4";
$actualWithEditorWhitespace = $method->invoke($controller, $inputWithEditorWhitespace);

if ($actualWithEditorWhitespace !== $expectedWithEditorWhitespace) {
    fwrite(STDERR, "FAILED: task report editor whitespace formatting\n");
    fwrite(STDERR, "Expected: ".json_encode($expectedWithEditorWhitespace, JSON_UNESCAPED_UNICODE)."\n");
    fwrite(STDERR, "Actual:   ".json_encode($actualWithEditorWhitespace, JSON_UNESCAPED_UNICODE)."\n");
    exit(1);
}

echo "cms-task-report-result-formatting: OK\n";
