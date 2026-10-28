<?php
namespace skeeks\cms\console\controllers;

use skeeks\cms\crm\LeadPipelineMigration;
use skeeks\cms\models\CmsSite;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Pipelines of deals (П2б): moving a site to «Входящие» and «Исходящие продажи».
 *
 *   php yii cms/lead-pipeline/init                    # report only, nothing changes
 *   php yii cms/lead-pipeline/init --apply            # apply (repeatable: a second run does nothing)
 *   php yii cms/lead-pipeline/init --site=6 --coldNames="База клиентов,Холодная база"
 */
class LeadPipelineController extends Controller
{
    /** @var bool apply the plan; without it only the report is printed */
    public $apply = false;

    /** @var int site id; the default site when empty */
    public $site = 0;

    /** @var string a deal whose source name starts with one of these (comma separated) is a cold call */
    public $coldNames = 'База клиентов';

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['apply', 'site', 'coldNames']);
    }

    public function actionInit()
    {
        $site = $this->site ? CmsSite::findOne((int)$this->site) : CmsSite::find()->default()->one();
        if (!$site) {
            $this->stderr("Сайт не найден.\n");
            return ExitCode::DATAERR;
        }
        $migration = new LeadPipelineMigration([
            'siteId' => (int)$site->id,
            'coldNames' => array_values(array_filter(array_map('trim', explode(',', (string)$this->coldNames)))),
        ]);

        $plan = $migration->plan();
        $this->stdout("Сайт: {$site->name} (№{$site->id})\n");
        $this->stdout("Воронка «Входящие»: {$plan['inbound']}\n");
        $this->stdout("Воронка «Исходящие продажи»: {$plan['outbound']}\n");
        $this->stdout("Этапов без воронки → «Входящие»: {$plan['stagesToInbound']}\n");
        $this->stdout("Холодных сделок → «Исходящие продажи»: {$plan['coldLeads']}\n");
        foreach ($plan['coldStages'] as $row) {
            $this->stdout('    этап «'.($row['name'] ?? 'без этапа')."»: {$row['cnt']}\n");
        }
        $this->stdout("Остальных сделок → «Входящие»: {$plan['otherLeads']}\n");
        $this->stdout("Сделок без истории (запись «состояние на дату перехода»): {$plan['snapshots']}\n");

        if (!$this->apply) {
            $this->stdout("\nНичего не изменено. Применить: --apply\n");
            return ExitCode::OK;
        }

        $done = $migration->apply();
        $this->stdout("\nПрименено: создано воронок ".count($done['created'])
            .($done['created'] ? ' ('.implode(', ', $done['created']).')' : '')
            ."; этапов во «Входящие» {$done['stagesToInbound']}; скопировано этапов в «Исходящие» {$done['stagesCopied']}"
            ."; холодных сделок {$done['coldLeads']}; остальных {$done['otherLeads']}; записей истории {$done['snapshots']}.\n");

        return ExitCode::OK;
    }
}
