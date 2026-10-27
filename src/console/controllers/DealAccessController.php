<?php
namespace skeeks\cms\console\controllers;

use skeeks\cms\deal\ProjectDealMigration;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\FileHelper;
use yii\helpers\Json;

/**
 * "Сделка как услуга": moving service projects into deals.
 *
 *   php yii cms/deal-access/migrate-projects                    # report only, nothing changes
 *   php yii cms/deal-access/migrate-projects --map=39:1649      # decide ambiguous projects
 *   php yii cms/deal-access/migrate-projects --apply            # apply; journal in cms_deal_project_migration
 *   php yii cms/deal-access/revert --run=<run key>              # undo a run (manual changes are kept)
 */
class DealAccessController extends Controller
{
    /** @var bool apply the plan; without it only the report is printed */
    public $apply = false;

    /** @var string projects whose name contains it */
    public $projectLike = 'Продвиж';

    /** @var string deals whose name or type name contains it */
    public $dealLike = 'Продвиж';

    /** @var string project:deal pairs, comma separated */
    public $map = '';

    /** @var string role code for project participants */
    public $role = 'marketer';

    /** @var string run key for revert */
    public $run = '';

    public function options($actionID)
    {
        if ($actionID === 'revert') {
            return array_merge(parent::options($actionID), ['run']);
        }
        return array_merge(parent::options($actionID), ['apply', 'projectLike', 'dealLike', 'map', 'role']);
    }

    public function actionMigrateProjects()
    {
        $migration = new ProjectDealMigration([
            'projectLike' => (string)$this->projectLike,
            'dealLike'    => (string)$this->dealLike,
            'map'         => $this->parseMap((string)$this->map),
            'roleCode'    => (string)$this->role,
        ]);

        $plan = $migration->plan();
        $this->printPlan($plan);

        $summary = ProjectDealMigration::companyPeopleSummary();
        $this->stdout("\nЛюди компаний: {$summary['people']}; «полный доступ» (is_root): {$summary['root']}, из них без выдачи прав: {$summary['root_no_grant']}.\n");
        $this->stdout("«Получать уведомления» (is_notify): {$summary['notify']} — останется как есть до подписок (шаг У4).\n");

        if (!$this->apply) {
            $this->stdout("\nПробный режим: ничего не изменено. Применить: --apply\n");
            return ExitCode::OK;
        }

        //The journal rows are already committed with each project; the file is only an export for people.
        $journal = $migration->apply($plan);
        $tasks = array_sum(array_map(function ($p) { return count($p['task_ids']); }, $journal['projects']));
        $grants = array_sum(array_map(function ($p) { return count($p['grant_ids']); }, $journal['projects']));
        $this->stdout("\nПрименено: проектов ".count($journal['projects']).", задач $tasks, выдано доступов $grants.\n");
        $this->stdout("Ключ запуска для отката: {$journal['run_key']}  (php yii cms/deal-access/revert --run={$journal['run_key']})\n");

        try {
            $dir = \Yii::getAlias('@runtime/deal-access');
            FileHelper::createDirectory($dir);
            $file = $dir.'/migrate-projects-'.$journal['run_key'].'.json';
            file_put_contents($file, Json::encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->stdout("Выгрузка: $file\n");
        } catch (\Throwable $e) {
            $this->stderr("Выгрузку записать не удалось ({$e->getMessage()}); журнал в базе сохранён.\n");
        }

        return ExitCode::OK;
    }

    public function actionRevert()
    {
        if (!$this->run) {
            $this->stderr("Укажите ключ запуска: --run=<ключ>\n");
            return ExitCode::USAGE;
        }
        $result = (new ProjectDealMigration())->revert((string)$this->run);
        if (!$result['projects']) {
            $this->stderr("Нечего откатывать: запуск не найден или уже откачен.\n");
            return ExitCode::DATAERR;
        }
        $this->stdout("Откат: проектов {$result['projects']}, у задач снята услуга — {$result['tasks']}, снято доступов — {$result['grants']}"
            .($result['kept_grants'] ? ", оставлено изменённых вручную — {$result['kept_grants']}" : '').".\n");
        return ExitCode::OK;
    }

    protected function parseMap(string $map): array
    {
        $result = [];
        foreach (array_filter(array_map('trim', explode(',', $map))) as $pair) {
            [$projectId, $dealId] = array_map('intval', explode(':', $pair) + [1 => 0]);
            if ($projectId && $dealId) {
                $result[$projectId] = $dealId;
            }
        }
        return $result;
    }

    protected function printPlan(array $plan): void
    {
        $ok = array_filter($plan, function ($row) { return (bool)$row['deal_id']; });
        $this->stdout("Проектов найдено: ".count($plan).", сопоставлено с услугой: ".count($ok)."\n\n");
        foreach ($plan as $row) {
            if ($row['deal_id']) {
                $line = sprintf("#%d → услуга #%d (%s): задач %d%s, участников %d, новых доступов %d",
                    $row['project_id'], $row['deal_id'], $row['reason'], $row['tasks'],
                    $row['tasks_other'] ? ", уже в другой услуге {$row['tasks_other']}" : '',
                    count($row['people']), count($row['people_new']));
            } else {
                $line = sprintf("#%d НЕ ПЕРЕНОСИТСЯ: %s%s", $row['project_id'], $row['reason'],
                    $row['candidates'] ? ' (услуги: #'.implode(', #', $row['candidates']).')' : '');
            }
            $this->stdout($line."\n");
        }
    }
}
