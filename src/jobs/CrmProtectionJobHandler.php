<?php
namespace skeeks\cms\jobs;

use skeeks\cms\crm\CrmProtection;
use skeeks\cms\job\contracts\JobReporterInterface;
use skeeks\cms\job\handlers\AbstractJobHandler;
use skeeks\cms\job\runtime\JobContext;

/**
 * The daily agent «Защита клиентов: снять истёкшую» (cms/crm/protection):
 * recalculates the protected clients, an expired protection becomes free
 * (CrmProtection::recalcMany()). The recalculation only follows payments and
 * services, so a repeated or retried run gives the same result.
 */
class CrmProtectionJobHandler extends AbstractJobHandler
{
    public function run(JobContext $context, JobReporterInterface $reporter): void
    {
        $reporter->setTotal(null);
        if (!CrmProtection::ready()) {
            $reporter->setResult(['recalculated' => 0, 'skipped' => 'the protection migration is not applied']);
            $reporter->setStage('completed');

            return;
        }
        $count = (new CrmProtection())->recalcMany(false);
        if ($count) {
            $reporter->countSuccess($count);
        }
        $reporter->setResult(['recalculated' => $count]);
        $reporter->setStage('completed');
    }
}
