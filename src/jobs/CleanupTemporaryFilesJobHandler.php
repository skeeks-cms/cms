<?php
namespace skeeks\cms\jobs;

use skeeks\cms\job\contracts\JobReporterInterface;
use skeeks\cms\job\exceptions\JobCancelledException;
use skeeks\cms\job\handlers\AbstractJobHandler;
use skeeks\cms\job\runtime\JobContext;

class CleanupTemporaryFilesJobHandler extends AbstractJobHandler
{
    public function run(JobContext $context, JobReporterInterface $reporter): void
    {
        $reporter->setTotal(null);
        $processed = 0;
        $stage = null;
        $checkpoint = static function (array $progress) use ($reporter, &$processed, &$stage) {
            if ($reporter->isCancelled()) { throw new JobCancelledException('Maintenance cancelled.'); }
            if ($stage !== $progress['stage']) {
                $stage = $progress['stage'];
                $reporter->setStage($stage);
            }
            $delta = $progress['processed'] - $processed;
            if ($delta > 0) { $reporter->advance($delta); $reporter->countSuccess($delta); }
            $processed = $progress['processed'];
            $reporter->heartbeat();
        };
        $checkpoint(['stage' => 'starting', 'processed' => 0]);
        $result = \Yii::createObject(\skeeks\yii2\ajaxfileupload\services\TemporaryFileCleanup::class)->run(
            \Yii::getAlias(\Yii::$app->getModule('ajaxfileupload')->private_tmp_dir), 3600, $checkpoint);
        $reporter->setResult($result);
        foreach ($result['skipped'] ?? [] as $skipped) {
            $reporter->countWarning();
            $reporter->warning('Cache unavailable to CLI: '.$skipped);
        }
        $reporter->setStage('completed');
    }
}
