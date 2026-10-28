<?php
namespace skeeks\cms\console\controllers;

use skeeks\cms\crm\CrmProtection;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * CRM jobs.
 *
 *   php yii cms/crm/protection          # the daily agent: recalculates protected clients (the expired ones become free)
 *   php yii cms/crm/protection --all    # every client with payments or services (after a data import)
 *   php yii cms/crm/assign-main         # protected clients without a main responsible: the plan only
 *   php yii cms/crm/assign-main --apply # assign them (a paying client always has a main responsible)
 */
class CrmController extends Controller
{
    /** @var bool every client with payments or services, not only the protected ones */
    public $all = false;

    /** @var bool assign-main: write the plan */
    public $apply = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), $actionID === 'protection' ? ['all'] : [], $actionID === 'assign-main' ? ['apply'] : []);
    }

    public function actionAssignMain()
    {
        $service = new CrmProtection();
        $plan = $service->mainPlan();
        $bySource = [];
        foreach ($plan as $row) {
            $this->stdout(sprintf("%s %d «%s» → %s (%s)\n", $row['code'] === 'company' ? 'Компания' : 'Клиент', $row['id'], $row['name'], $row['user'], $row['source']));
            $bySource[$row['source']] = ($bySource[$row['source']] ?? 0) + 1;
        }
        $this->stdout("\nВсего: ".count($plan)."\n");
        foreach ($bySource as $source => $count) {
            $this->stdout("  {$source}: {$count}\n");
        }
        if ($this->apply) {
            $this->stdout('Назначено: '.$service->applyMainPlan($plan)."\n");
        } else {
            $this->stdout("Ничего не записано. Записать: --apply\n");
        }

        return ExitCode::OK;
    }

    public function actionProtection()
    {
        if (!CrmProtection::ready()) {
            $this->stdout("Миграция защиты клиентов ещё не применена.\n");

            return ExitCode::OK;
        }
        $count = (new CrmProtection())->recalcMany((bool)$this->all);
        $this->stdout("Пересчитана защита клиентов: {$count}.\n");

        return ExitCode::OK;
    }
}
