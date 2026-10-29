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
 *   php yii cms/crm/assign-history-main [--user=1]          # clients with a history and no main responsible: the plan only
 *   php yii cms/crm/assign-history-main [--user=1] --apply  # assign: by the last payment or service, else --user
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
            $actionID === 'assign-old-clients' ? ['apply', 'user', 'before'] : [], $actionID === 'assign-history-main' ? ['apply', 'user'] : [],
            $actionID === 'assign-orphan-main' ? ['apply', 'user'] : [], $actionID === 'assign-pool-main' ? ['apply', 'user'] : []);
    }

    /**
     * Clients with a history — a payment, a bill, a service or a deal ever,
     * not an unsorted incoming deal — and without a main responsible (owner's
     * decision 29.10.2026: such a client always has one, it is not in the
     * general pool). The main one: by the last payment or service, else --user
     * (the responsible by default, CrmAccess::defaultResponsible()). Each one is written to the journal.
     */
    public function actionAssignHistoryMain()
    {
        $service = new CrmProtection();
        $fallback = $this->fallbackUser($service);
        $plan = $service->historyMainPlan($fallback);
        $byCode = [];
        $bySource = [];
        $byUser = [];
        foreach ($plan as $row) {
            $byCode[$row['code']] = ($byCode[$row['code']] ?? 0) + 1;
            $bySource[$row['source']] = ($bySource[$row['source']] ?? 0) + 1;
            $byUser[$row['user']] = ($byUser[$row['user']] ?? 0) + 1;
        }
        $this->stdout('Клиенты с историей без основного ответственного: '.count($plan)
            .' (компаний '.($byCode['company'] ?? 0).', людей '.($byCode['client'] ?? 0).")
");
        $this->stdout("По основанию:
");
        foreach ($bySource as $source => $count) {
            $this->stdout("  {$source}: {$count}
");
        }
        arsort($byUser);
        $this->stdout("Кому:
");
        foreach ($byUser as $user => $count) {
            $this->stdout("  {$user}: {$count}
");
        }
        if ($this->apply) {
            $this->stdout('Назначено: '.$service->applyHistoryMainPlan($plan)."
");
        } else {
            $this->stdout("Ничего не записано. Записать: --apply
");
        }

        return ExitCode::OK;
    }

    /**
     * Clients without any employee — the former general pool (owner's decision
     * 29.10.2026, п. 13а: a CRM client always has a main responsible). The main
     * one: by the last payment or service, else --user (the responsible by
     * default). Users of the site who registered themselves and ordered nothing
     * are not CRM clients and are left out. The plan by default; --apply
     * writes, each one to the journal.
     */
    public function actionAssignPoolMain()
    {
        $service = new CrmProtection();
        $fallback = $this->fallbackUser($service);
        $plan = $service->poolMainPlan($fallback);
        $byCode = [];
        $bySource = [];
        $byUser = [];
        foreach ($plan as $row) {
            $byCode[$row['code']] = ($byCode[$row['code']] ?? 0) + 1;
            $bySource[$row['source']] = ($bySource[$row['source']] ?? 0) + 1;
            $byUser[$row['user']] = ($byUser[$row['user']] ?? 0) + 1;
        }
        $this->stdout('Клиенты без основного ответственного (бывший общий пул): '.count($plan)
            .' (компаний '.($byCode['company'] ?? 0).', людей '.($byCode['client'] ?? 0).")\n");
        $this->stdout("По основанию:\n");
        foreach ($bySource as $source => $count) {
            $this->stdout("  {$source}: {$count}\n");
        }
        arsort($byUser);
        $this->stdout("Кому:\n");
        foreach ($byUser as $user => $count) {
            $this->stdout("  {$user}: {$count}\n");
        }
        if ($this->apply) {
            $this->stdout('Назначено: '.$service->applyHistoryMainPlan($plan)."\n");
        } else {
            $this->stdout("Ничего не записано. Записать: --apply\n");
        }

        return ExitCode::OK;
    }

    /**
     * Clients with participants and without a main responsible (owner's
     * decision 29.10.2026: participants only with the main one, the pool has
     * none). The main one: an active participant with "full", else by the last
     * payment or service, else --user (the responsible by default). The plan
     * by default; --apply writes, each one to the journal.
     */
    public function actionAssignOrphanMain()
    {
        $service = new CrmProtection();
        $fallback = $this->fallbackUser($service);
        $plan = $service->orphanMainPlan($fallback);
        $byCode = [];
        $bySource = [];
        $byUser = [];
        foreach ($plan as $row) {
            $byCode[$row['code']] = ($byCode[$row['code']] ?? 0) + 1;
            $bySource[$row['source']] = ($bySource[$row['source']] ?? 0) + 1;
            $byUser[$row['user']] = ($byUser[$row['user']] ?? 0) + 1;
        }
        $this->stdout('Клиенты с участниками без основного ответственного: '.count($plan)
            .' (компаний '.($byCode['company'] ?? 0).', людей '.($byCode['client'] ?? 0).")\n");
        $this->stdout("По основанию:\n");
        foreach ($bySource as $source => $count) {
            $this->stdout("  {$source}: {$count}\n");
        }
        arsort($byUser);
        $this->stdout("Кому:\n");
        foreach ($byUser as $user => $count) {
            $this->stdout("  {$user}: {$count}\n");
        }
        if ($this->apply) {
            $this->stdout('Назначено: '.$service->applyHistoryMainPlan($plan)."\n");
        } else {
            $this->stdout("Ничего не записано. Записать: --apply\n");
        }

        return ExitCode::OK;
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

    /**
     * The employee of `--user`, else the responsible by default
     * (CrmAccess::defaultResponsible(): the CRM setting, else the first
     * administrator); the plan says who it is and why.
     */
    private function fallbackUser(CrmProtection $service): ?int
    {
        [$userId, $why] = $service->fallback($this->user ? (int)$this->user : null);
        $user = $userId ? \skeeks\cms\models\CmsUser::findOne($userId) : null;
        $this->stdout('Если по истории никого: '.($user ? $user->shortDisplayName.' (id '.$userId.')' : 'никого').' — '.$why."\n");

        return $userId;
    }
}
