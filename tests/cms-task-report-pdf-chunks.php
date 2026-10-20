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

class TaskReportPdfChunkWriter
{
    public $chunks = [];

    public function WriteHTML($html)
    {
        $this->chunks[] = $html;
    }
}

$reflection = new ReflectionClass(AdminCmsTaskController::class);
$controller = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('writeTaskReportPdfHtml');
$method->setAccessible(true);

$parts = [
    '<html><head><style>body { color: #222; }</style></head><body>',
    '<div class="task-item">'.str_repeat('Первая задача. ', 5000).'</div>',
    '<div class="task-item">Вторая задача.</div></body></html>',
];
$html = implode('<!-- skeeks-mpdf-chunk -->', $parts);
$writer = new TaskReportPdfChunkWriter();
$method->invoke($controller, $writer, $html);

if ($writer->chunks !== $parts) {
    throw new RuntimeException('PDF report HTML was not streamed by the template chunk boundaries.');
}

$view = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-task/report-pdf.php');
if (substr_count($view, '<!-- skeeks-mpdf-chunk -->') < 5) {
    throw new RuntimeException('PDF report template does not provide chunk boundaries for all report layouts.');
}

echo "cms-task-report-pdf-chunks: OK (streamed HTML)\n";
