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
 *   php yii cms/crm/assign-old-clients --user=1 --before=2026-10-29          # old persons of the pool: the plan only
 *   php yii cms/crm/assign-old-clients --user=1 --before=2026-10-29 --apply  # make the employee their main responsible
 */
class CrmController extends Controller
{
    /** @var bool every client with payments or services, not only the protected ones */
    public $all = false;

    /** @var bool assign-main, assign-old-clients: write the plan */
    public $apply = false;

    /** @var int assign-old-clients: the employee who becomes the main responsible */
    public $user;

    /** @var string assign-old-clients: only persons created before this date (Y-m-d), required */
    public $before;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), $actionID === 'protection' ? ['all'] : [], $actionID === 'assign-main' ? ['apply'] : [],
            $actionID === 'assign-old-clients' ? ['apply', 'user', 'before'] : []);
    }

    /**
     * The old base of client persons in the general pool (owner's decision
     * 29.10.2026): no company, no employee of their own, created before
     * --before. The employee --user becomes their main responsible; each one is
     * written to the journal of the access. People who come later stay in the pool.
     */
    public function actionAssignOldClients()
    {
        $before = $this->before ? strtotime($this->before.' 00:00:00') : false;
        $user = $this->user ? \skeeks\cms\models\CmsUser::find()->andWhere(['id' => (int)$this->user, 'is_worker' => 1, 'is_active' => 1])->one() : null;
        if (!$before || !$user) {
            $this->stderr("Укажите --user=<id активного сотрудника> и --before=<ГГГГ-ММ-ДД>.\n");

            return ExitCode::USAGE;
        }
        $service = new CrmProtection();
        $people = $service->oldBasePeople($before, \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null);
        $years = [];
        foreach ($people as $person) {
            $year = date('Y', (int)$person->created_at);
            $years[$year] = ($years[$year] ?? 0) + 1;
        }
        ksort($years);
        $this->stdout('Люди без компании и без ответственного, созданы до '.date('d.m.Y', $before).': '.count($people)."\n");
        foreach ($years as $year => $count) {
            $this->stdout("  {$year}: {$count}\n");
        }
        $this->stdout('Основной ответственный: '.$user->shortDisplayName.' (id '.(int)$user->id.")\n");
        if ($this->apply) {
            $this->stdout('Назначено: '.$service->applyOldBase($people, (int)$user->id)."\n");
        } else {
            $this->stdout("Ничего не записано. Записать: --apply\n");
        }

        return ExitCode::OK;
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
