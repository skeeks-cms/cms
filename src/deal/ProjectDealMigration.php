<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\deal;

use skeeks\cms\models\CmsClientAccess;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealRole;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsProject2user;
use skeeks\cms\models\CmsTask;
use yii\base\BaseObject;
use yii\db\Query;

/**
 * Moves the client side of service projects into deals ("Сделка как услуга", step У2):
 * - tasks of the project get the deal (the project stays on the task for internal work);
 * - participants of the project (CmsProject2user) get a grant on the deal with a role.
 *
 * A project is matched to a deal of its owner (company, otherwise client) whose name
 * or type contains $dealLike: the only active one, otherwise the only one at all;
 * among several, the one that mentions the project's domain. Ambiguous projects are
 * left untouched and reported; an explicit map decides them.
 *
 * plan() only reads. apply() changes data and returns a journal for revert().
 */
class ProjectDealMigration extends BaseObject
{
    /** @var string projects whose name contains it */
    public $projectLike = 'Продвиж';

    /** @var string deals whose name or type name contains it */
    public $dealLike = 'Продвиж';

    /** @var array project id => deal id, decided by a person */
    public $map = [];

    /** @var string code of the role for project participants */
    public $roleCode = CmsDealRole::CODE_MARKETER;

    /**
     * @return array[] one row per project: project, deal (or null), reason, candidates, tasks, people
     */
    public function plan(): array
    {
        $projects = CmsProject::find()
            ->andWhere(['like', CmsProject::tableName().'.name', $this->projectLike])
            ->orderBy([CmsProject::tableName().'.id' => SORT_ASC])
            ->all();

        $rows = [];
        foreach ($projects as $project) {
            $rows[] = $this->planProject($project);
        }
        return $rows;
    }

    protected function planProject(CmsProject $project): array
    {
        $row = [
            'project_id'   => (int)$project->id,
            'project_name' => (string)$project->name,
            'company_id'   => $project->cms_company_id ? (int)$project->cms_company_id : null,
            'deal_id'      => null,
            'deal_name'    => null,
            'reason'       => '',
            'candidates'   => [],
            'tasks'        => 0,
            'tasks_other'  => 0,
            'people'       => [],
            'people_new'   => [],
        ];

        $taskQuery = CmsTask::find()->andWhere([CmsTask::tableName().'.cms_project_id' => $project->id]);
        $row['people'] = array_map('intval', CmsProject2user::find()->select('cms_user_id')->andWhere(['cms_project_id' => $project->id])->column());

        if (isset($this->map[(int)$project->id])) {
            $deal = CmsDeal::findOne((int)$this->map[(int)$project->id]);
            if (!$deal) {
                $row['reason'] = 'сделка из карты не найдена';
                return $row;
            }
            if (!$this->sameOwner($project, $deal)) {
                $row['reason'] = 'сделка из карты другого клиента';
                return $row;
            }
            $row['reason'] = 'по карте';
        } elseif (!$project->cms_company_id && !$project->cms_user_id) {
            $row['reason'] = 'у проекта нет компании или клиента';
            return $row;
        } else {
            $candidates = $this->candidates($project);
            $row['candidates'] = array_map(function (CmsDeal $deal) {
                return (int)$deal->id;
            }, $candidates);
            [$deal, $row['reason']] = $this->choose($project, $candidates);
            if (!$deal) {
                return $row;
            }
        }

        $row['deal_id'] = (int)$deal->id;
        $row['deal_name'] = (string)$deal->name;
        $row['tasks'] = (int)(clone $taskQuery)->andWhere([CmsTask::tableName().'.cms_deal_id' => null])->count();
        $row['tasks_other'] = (int)(clone $taskQuery)
            ->andWhere(['not', [CmsTask::tableName().'.cms_deal_id' => null]])
            ->andWhere(['!=', CmsTask::tableName().'.cms_deal_id', (int)$deal->id])
            ->count();
        foreach ($row['people'] as $userId) {
            if (!CmsClientAccess::find()->andWhere(['cms_user_id' => $userId, 'scope_key' => 'd:'.(int)$deal->id])->exists()) {
                $row['people_new'][] = $userId;
            }
        }

        return $row;
    }

    /**
     * @return CmsDeal[]
     */
    protected function candidates(CmsProject $project): array
    {
        $dealTable = CmsDeal::tableName();
        $query = CmsDeal::find()
            ->joinWith('dealType as dealType')
            ->andWhere(['or', ['like', $dealTable.'.name', $this->dealLike], ['like', 'dealType.name', $this->dealLike]])
            ->orderBy([$dealTable.'.id' => SORT_ASC]);
        if ($project->cms_company_id) {
            $query->andWhere([$dealTable.'.cms_company_id' => (int)$project->cms_company_id]);
        } else {
            $query->andWhere([$dealTable.'.cms_company_id' => null, $dealTable.'.cms_user_id' => (int)$project->cms_user_id]);
        }
        return $query->all();
    }

    /**
     * @param CmsDeal[] $candidates
     * @return array [CmsDeal|null, reason]
     */
    protected function choose(CmsProject $project, array $candidates): array
    {
        if (!$candidates) {
            return [null, 'у клиента нет подходящей сделки'];
        }
        $active = array_values(array_filter($candidates, function (CmsDeal $deal) {
            return (bool)$deal->is_active;
        }));
        if (count($active) === 1) {
            return [$active[0], 'единственная активная сделка'];
        }
        if (!$active && count($candidates) === 1) {
            return [$candidates[0], 'единственная сделка (не активна)'];
        }

        $domain = static::domainOf((string)$project->name.' '.(string)$project->description);
        if ($domain) {
            $pool = $active ?: $candidates;
            $byDomain = array_values(array_filter($pool, function (CmsDeal $deal) use ($domain) {
                return mb_stripos((string)$deal->name.' '.(string)$deal->description, $domain) !== false;
            }));
            if (count($byDomain) === 1) {
                return [$byDomain[0], 'совпал домен '.$domain];
            }
        }

        return [null, 'несколько сделок: нужна карта'];
    }

    protected function sameOwner(CmsProject $project, CmsDeal $deal): bool
    {
        if ($project->cms_company_id) {
            return (int)$deal->cms_company_id === (int)$project->cms_company_id;
        }
        return !$deal->cms_company_id && (int)$deal->cms_user_id === (int)$project->cms_user_id;
    }

    public static function domainOf(string $text): ?string
    {
        if (preg_match('/([a-z0-9\-]+(?:\.[a-z0-9\-]+)*\.(?:[a-z]{2,}|рф))/iu', $text, $m)) {
            return mb_strtolower(preg_replace('/^www\./i', '', $m[1]));
        }
        return null;
    }

    const JOURNAL_TABLE = '{{%cms_deal_project_migration}}';

    /**
     * Applies the plan rows that have a deal. Every project is moved in its own
     * transaction together with its journal row (cms_deal_project_migration), so
     * whatever was moved can always be undone by the run key.
     *
     * @return array ['run_key' => ..., 'projects' => [...]] — also an export for people
     */
    public function apply(array $plan): array
    {
        $role = CmsDealRole::find()->andWhere(['code' => $this->roleCode])->one();
        if (!$role) {
            throw new \yii\base\InvalidConfigException("Роль «{$this->roleCode}» не найдена.");
        }
        if (!CmsTask::getDb()->getTableSchema(self::JOURNAL_TABLE, true)) {
            throw new \yii\base\InvalidConfigException('Нет журнала переноса: примените миграции (cms/migrate).');
        }
        /** @var DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');

        $runKey = date('Ymd-His').'-'.substr(md5(uniqid('', true)), 0, 6);
        $journal = ['run_key' => $runKey, 'created_at' => time(), 'role' => $this->roleCode, 'projects' => []];
        foreach ($plan as $row) {
            if (!$row['deal_id']) {
                continue;
            }
            $deal = CmsDeal::findOne((int)$row['deal_id']);
            $transaction = CmsTask::getDb()->beginTransaction();
            try {
                $taskIds = array_map('intval', CmsTask::find()
                    ->select(CmsTask::tableName().'.id')
                    ->andWhere([CmsTask::tableName().'.cms_project_id' => $row['project_id'], CmsTask::tableName().'.cms_deal_id' => null])
                    ->column());
                if ($taskIds) {
                    CmsTask::updateAll(['cms_deal_id' => (int)$deal->id], ['id' => $taskIds, 'cms_deal_id' => null]);
                }

                $grantIds = [];
                foreach ($row['people_new'] as $userId) {
                    $grant = new CmsClientAccess([
                        'cms_user_id'      => (int)$userId,
                        'cms_deal_id'      => (int)$deal->id,
                        'cms_deal_role_id' => (int)$role->id,
                        'comment'          => 'Перенесено из проекта «'.$row['project_name'].'»',
                    ]);
                    if (!$grant->save()) {
                        throw new \RuntimeException("Доступ человеку #{$userId}: ".implode('; ', $grant->getFirstErrors()));
                    }
                    //A snapshot of the grant: revert removes it only while it is exactly as created.
                    $grantIds[] = ['id' => (int)$grant->id, 'snapshot' => static::grantSnapshot(CmsClientAccess::findOne($grant->id))];
                }

                if ($taskIds || $grantIds) {
                    $dealAccess->writeDealLog($deal, 'Из проекта «'.$row['project_name'].'» перенесено задач: '.count($taskIds)
                        .($grantIds ? ', выдан доступ участникам проекта: '.count($grantIds).' (роль «'.$role->name.'»)' : '').'.');
                }
                CmsTask::getDb()->createCommand()->insert(self::JOURNAL_TABLE, [
                    'created_at'     => time(),
                    'run_key'        => $runKey,
                    'cms_project_id' => (int)$row['project_id'],
                    'cms_deal_id'    => (int)$deal->id,
                    'task_ids'       => \yii\helpers\Json::encode($taskIds),
                    'grant_ids'      => \yii\helpers\Json::encode($grantIds),
                ])->execute();
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            $journal['projects'][] = [
                'project_id' => (int)$row['project_id'],
                'deal_id'    => (int)$deal->id,
                'task_ids'   => $taskIds,
                'grant_ids'  => $grantIds,
            ];
        }

        return $journal;
    }

    /**
     * Undoes a run by its key from the journal. Only what is still as the run left it:
     * a task loses the deal only if it still points at that deal; a grant is removed
     * only if nobody changed it afterwards (updated later or configured by hand) —
     * changed grants are kept and reported.
     *
     * @return array ['projects' => n, 'tasks' => n, 'grants' => n, 'kept_grants' => n]
     */
    public function revert(string $runKey): array
    {
        /** @var DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');
        $db = CmsTask::getDb();
        $result = ['projects' => 0, 'tasks' => 0, 'grants' => 0, 'kept_grants' => 0];

        $rows = (new Query())->from(self::JOURNAL_TABLE)
            ->where(['run_key' => $runKey, 'reverted_at' => null])
            ->orderBy(['id' => SORT_ASC])
            ->all($db);

        foreach ($rows as $item) {
            $taskIds = (array)\yii\helpers\Json::decode((string)$item['task_ids']);
            $snapshots = [];
            foreach ((array)\yii\helpers\Json::decode((string)$item['grant_ids']) as $entry) {
                $snapshots[(int)$entry['id']] = (string)$entry['snapshot'];
            }
            $transaction = $db->beginTransaction();
            try {
                $tasks = $taskIds ? CmsTask::updateAll(['cms_deal_id' => null], ['id' => $taskIds, 'cms_deal_id' => (int)$item['cms_deal_id']]) : 0;
                $grants = 0;
                $kept = 0;
                foreach (CmsClientAccess::find()->andWhere(['id' => array_keys($snapshots)])->all() as $grant) {
                    if (static::grantSnapshot($grant) !== $snapshots[(int)$grant->id]) {
                        $kept++;
                        continue;
                    }
                    $grant->delete();
                    $grants++;
                }
                if ($deal = CmsDeal::findOne((int)$item['cms_deal_id'])) {
                    $dealAccess->writeDealLog($deal, 'Перенос из проекта #'.(int)$item['cms_project_id'].' отменён: задач '.$tasks
                        .', доступов снято '.$grants.($kept ? ', оставлено изменённых вручную '.$kept : '').'.');
                }
                $db->createCommand()->update(self::JOURNAL_TABLE, [
                    'reverted_at' => time(),
                    'revert_note' => "задач {$tasks} из ".count($taskIds).", доступов снято {$grants}, оставлено {$kept}",
                ], ['id' => (int)$item['id']])->execute();
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
            $result['projects']++;
            $result['tasks'] += $tasks;
            $result['grants'] += $grants;
            $result['kept_grants'] += $kept;
        }
        return $result;
    }

    /**
     * Everything that defines what a grant opens: any later change (scope, role,
     * full flag, levels, comment) gives another snapshot, whatever second it happened in.
     */
    public static function grantSnapshot(CmsClientAccess $grant): string
    {
        $levels = $grant->getLevels();
        ksort($levels);
        return md5(\yii\helpers\Json::encode([
            'user'   => (int)$grant->cms_user_id,
            'scope'  => (string)$grant->scope_key,
            'role'   => (int)$grant->cms_deal_role_id,
            'full'   => (int)$grant->is_full,
            'custom' => (int)$grant->is_custom,
            'levels' => $levels,
            'comment'=> (string)$grant->comment,
        ]));
    }

    /**
     * Counts of the company people flags for the report: is_root (already carried
     * over to grants by the migration) and is_notify (subscriptions, step У4).
     */
    public static function companyPeopleSummary(): array
    {
        $table = '{{%cms_company2user}}';
        return [
            'people'          => (int)(new Query())->from($table)->count(),
            'root'            => (int)(new Query())->from($table)->where(['is_root' => 1])->count(),
            'root_no_grant'   => (int)(new Query())->from(['cu' => $table])
                ->where(['cu.is_root' => 1])
                ->andWhere(['not exists', (new Query())->from(['a' => '{{%cms_client_access}}'])
                    ->where('a.cms_user_id = cu.cms_user_id')
                    ->andWhere("a.scope_key = CONCAT('c:', cu.cms_company_id)")
                    ->andWhere(['a.is_full' => 1])])
                ->count(),
            'notify'          => (int)(new Query())->from($table)->where(['is_notify' => 1])->count(),
        ];
    }
}
