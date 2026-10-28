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
 */
class CrmController extends Controller
{
    /** @var bool every client with payments or services, not only the protected ones */
    public $all = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), $actionID === 'protection' ? ['all'] : []);
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
