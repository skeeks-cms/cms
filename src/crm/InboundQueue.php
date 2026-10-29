<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDepartment2worker;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use yii\db\Query;
use yii\helpers\Html;

/**
 * «Входящие заявки» (owner's decision 29.10.2026, cms-communication
 * ARCHITECTURE.md п. 13б): who sorts the requests of «Неразобранное» and how.
 * The queue belongs to the pipeline the deal is in (CmsLeadPipeline: its
 * employees, departments and mode; a request lands in a pipeline by its source,
 * CmsLeadPipeline::forSource()); a pipeline without its own queue takes the
 * CRM settings «Входящие заявки» (CrmSettings::$inboundWorkerIds,
 * $inboundDepartmentIds, $inboundMode). A deal moved to another pipeline is
 * seen and told by the queue of the new one.
 *
 * - MODE_ALL — every employee of the queue sees every unsorted deal
 *   (CmsLeadQuery::forManager()) and is notified;
 * - MODE_ROUND — a request is given at once to the next employee of the queue
 *   (the turn is kept in cms_crm_inbound_rotation, locked in the transaction
 *   that creates the deal); he sorts it.
 *
 * A request whose client is found goes to the client's main responsible when
 * CrmSettings::$inboundToMain is set and the found records have one main
 * responsible: the deal skips «Неразобранное». Without a queue (a site that
 * never set it) everything stays as before: the administrators and the
 * employees who see the found client.
 */
class InboundQueue
{
    public const MODE_ALL = 'all';
    public const MODE_ROUND = 'round';

    public const QUEUE_DEFAULT = 'default';

    /** @var int[][] "site:workers:departments" => members, per request */
    private static array $members = [];

    public static function modes(): array
    {
        return [
            self::MODE_ALL => 'Видят все из списка',
            self::MODE_ROUND => 'По кругу — сразу одному',
        ];
    }

    public static function settings(): ?CrmSettings
    {
        return \Yii::$app->has('crmSettings') ? \Yii::$app->crmSettings : null;
    }

    /** The rotation table exists (the migration may run after the code update). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $ready = \Yii::$app->db->getTableSchema('{{%cms_crm_inbound_rotation}}', true) !== null;
        }

        return $ready;
    }

    /** The mode of the CRM settings (the default of the pipelines). */
    public static function mode(): string
    {
        $settings = self::settings();

        return $settings && $settings->inboundMode === self::MODE_ROUND ? self::MODE_ROUND : self::MODE_ALL;
    }

    /**
     * The queue of a pipeline: its own employees and departments, else those
     * of the CRM settings; its own mode, else the one of the CRM settings.
     *
     * @return array ['key' => string (the turn), 'members' => int[], 'mode' => string]
     */
    public static function queueOf(?CmsLeadPipeline $pipeline, ?int $siteId = null): array
    {
        $settings = self::settings();
        $mode = $pipeline && CmsLeadPipeline::inboundReady() && $pipeline->inbound_mode ? (string)$pipeline->inbound_mode : self::mode();
        if ($pipeline && CmsLeadPipeline::inboundReady() && $pipeline->hasOwnQueue) {
            return ['key' => 'pipeline:'.(int)$pipeline->id, 'members' => self::membersOf($pipeline->inboundWorkerIds, $pipeline->inboundDepartmentIds, $siteId), 'mode' => $mode];
        }

        return [
            'key' => $pipeline && $mode !== self::mode() ? 'pipeline:'.(int)$pipeline->id : self::QUEUE_DEFAULT,
            'members' => $settings ? self::membersOf((array)$settings->inboundWorkerIds, (array)$settings->inboundDepartmentIds, $siteId) : [],
            'mode' => $mode,
        ];
    }

    /** The queue of the pipeline a deal is in. */
    public static function queueOfLead(CmsLead $lead): array
    {
        $siteId = $lead->cms_site_id ? (int)$lead->cms_site_id : null;

        return self::queueOf($lead->getEffectivePipeline(), $siteId);
    }

    /** The queue of the CRM settings is set (the default of the pipelines). */
    public static function configured(?int $siteId = null): bool
    {
        return (bool)self::memberIds($siteId);
    }

    /** @return int[] the employees of the queue of the CRM settings */
    public static function memberIds(?int $siteId = null): array
    {
        return self::queueOf(null, $siteId)['members'];
    }

    /**
     * Active employees with the deals right among the given ones and the
     * employees of the departments, ordered by id (the order of the turn).
     *
     * @return int[]
     */
    public static function membersOf(array $workerIds, array $departmentIds, ?int $siteId = null): array
    {
        $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
        $departmentIds = array_values(array_unique(array_filter(array_map('intval', $departmentIds))));
        $key = (int)$siteId.':'.implode(',', $workerIds).':'.implode(',', $departmentIds);
        if (array_key_exists($key, self::$members)) {
            return self::$members[$key];
        }
        $ids = $workerIds;
        if ($departmentIds) {
            $ids = array_merge($ids, array_map('intval', CmsDepartment2worker::find()
                ->andWhere(['cms_department_id' => $departmentIds])->select('worker_id')->column()));
        }
        $ids = array_values(array_unique(array_filter($ids)));
        $result = [];
        if ($ids) {
            $query = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $ids, CmsUser::tableName().'.is_active' => 1]);
            if ($siteId) {
                $query->cmsSite($siteId);
            }
            foreach ($query->select(CmsUser::tableName().'.id')->column() as $id) {
                if (\Yii::$app->authManager->checkAccess((int)$id, 'cms/admin-lead')) {
                    $result[] = (int)$id;
                }
            }
            sort($result);
        }

        return self::$members[$key] = $result;
    }

    public static function isMember(int $userId, ?int $siteId = null): bool
    {
        return in_array($userId, self::memberIds($siteId), true);
    }

    /**
     * The unsorted deals nobody was given that the employee sees as a member
     * of a queue «видят все из списка» (CmsLeadQuery::forManager()): of the
     * pipelines whose queue he is in; by turns only the given one sees a deal
     * (as its responsible). Null — none.
     */
    public static function unsortedCondition(int $userId, string $leadTable): ?array
    {
        $siteId = \Yii::$app->has('skeeks') && \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
        $pipelines = CmsLead::pipelineReady() ? CmsLeadPipeline::forSite($siteId) : [];
        $where = [];
        if (!$pipelines) {
            $queue = self::queueOf(null, $siteId);
            if ($queue['mode'] === self::MODE_ALL && in_array($userId, $queue['members'], true)) {
                $where[] = '1=1';
            }
        }
        foreach ($pipelines as $pipeline) {
            $queue = self::queueOf($pipeline, $siteId);
            if ($queue['mode'] === self::MODE_ALL && in_array($userId, $queue['members'], true)) {
                $where[] = $pipeline->leadCondition($leadTable);
            }
        }
        if (!$where) {
            return null;
        }

        return ['and', [$leadTable.'.status' => CmsLead::STATUS_NEW], [$leadTable.'.executor_id' => null],
            count($where) === 1 ? $where[0] : array_merge(['or'], $where)];
    }

    /** Forgets the members read in this request (tests, a changed setting). */
    public static function reset(): void
    {
        self::$members = [];
        CmsLeadPipeline::clearCache();
    }

    /**
     * Who is notified about a new unsorted deal: the queue of its pipeline
     * (MODE_ALL), the employee it was given to and the administrators with the
     * deals right (their own notification settings decide). Null — no queue:
     * the old rule (CmsLead::availableManagerIds()).
     *
     * @return int[]|null
     */
    public static function notifyIds(CmsLead $lead): ?array
    {
        $siteId = $lead->cms_site_id ? (int)$lead->cms_site_id : null;
        $queue = self::queueOfLead($lead);
        if (!$queue['members']) {
            return null;
        }
        $ids = $queue['mode'] === self::MODE_ALL ? $queue['members'] : [];
        $admins = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1]);
        if ($siteId) {
            $admins->cmsSite($siteId);
        }
        foreach ($admins->select(CmsUser::tableName().'.id')->column() as $id) {
            if (\Yii::$app->authManager->checkAccess((int)$id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)
                && \Yii::$app->authManager->checkAccess((int)$id, 'cms/admin-lead')) {
                $ids[] = (int)$id;
            }
        }
        if ($lead->executor_id) {
            $ids[] = (int)$lead->executor_id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * The next employee of the turn of a queue. Locks the row of the queue, so
     * it must run in the transaction that saves the deal: a parallel request
     * waits and gets the employee after.
     *
     * @param int[]|null $members the queue; null — the one of the CRM settings
     */
    public static function next(int $siteId, string $queue = self::QUEUE_DEFAULT, ?array $members = null): ?int
    {
        $members = $members ?? self::memberIds($siteId);
        if (!$members || !self::ready()) {
            return null;
        }
        $db = \Yii::$app->db;
        $own = $db->getTransaction() === null ? $db->beginTransaction() : null;
        try {
            $table = '{{%cms_crm_inbound_rotation}}';
            self::ensureRow($siteId, $queue);
            // Only the exclusive lock of the existing row here: a shared lock of an insert upgraded to it
            // made two requests wait for each other (deadlock, release audit F3).
            $row = $db->createCommand('SELECT id, last_user_id FROM '.$db->quoteTableName($table).' WHERE cms_site_id = :site AND queue = :queue FOR UPDATE',
                [':site' => $siteId, ':queue' => $queue])->queryOne();
            if (!$row) {
                throw new \RuntimeException('Очередь «Входящие заявки» не найдена.');
            }
            $last = $row && $row['last_user_id'] !== null ? (int)$row['last_user_id'] : null;
            $next = $members[0];
            foreach ($members as $id) {
                if ($last !== null && $id > $last) {
                    $next = $id;
                    break;
                }
            }
            $db->createCommand()->update($table, ['last_user_id' => $next, 'updated_at' => time()], ['id' => (int)$row['id']])->execute();
            if ($own) {
                $own->commit();
            }

            return $next;
        } catch (\Throwable $e) {
            if ($own && $own->isActive) {
                $own->rollBack();
            }
            throw $e;
        }
    }

    /**
     * The row of a queue exists before the transaction locks it: created by its own short connection
     * (autocommit), so the transaction of the deal never holds an insert or a gap lock of it. A queue is
     * created once; requests that meet there wait for each other on one statement, a rare deadlock of
     * that statement is repeated.
     */
    private static function ensureRow(int $siteId, string $queue): void
    {
        $db = \Yii::$app->db;
        $table = '{{%cms_crm_inbound_rotation}}';
        $where = ['cms_site_id' => $siteId, 'queue' => $queue];
        if ((new Query())->from($table)->andWhere($where)->exists($db)) {
            return;
        }
        $own = clone $db;
        try {
            for ($attempt = 1; ; $attempt++) {
                try {
                    $own->createCommand('INSERT IGNORE INTO '.$own->quoteTableName($table).' (cms_site_id, queue, updated_at) VALUES (:site, :queue, :time)',
                        [':site' => $siteId, ':queue' => $queue, ':time' => time()])->execute();

                    return;
                } catch (\yii\db\Exception $e) {
                    if ($attempt >= 5 || !\skeeks\cms\services\CmsLeadService::isLockConflict($e)) {
                        throw $e;
                    }
                    usleep(random_int(5000, 30000) * $attempt);
                }
            }
        } finally {
            $own->close();
        }
    }

    /**
     * The main responsible of the client a request names (CrmMatcher, strong
     * evidence only): one for all the found records, an active employee with
     * the deals right. The company and the person are linked only when one
     * of each is found. Null — not found, several main responsibles, or none.
     *
     * @return array|null ['executorId' => int, 'companyId' => ?int, 'clientId' => ?int]
     */
    public static function mainOf(array $input): ?array
    {
        $access = CrmAccess::instance();
        $companies = [];
        $clients = [];
        $mains = [];
        foreach (CrmMatcher::instance()->rawFind($input) as $candidate) {
            $strong = (bool)array_filter($candidate['reasons'] ?? [], static fn($reason) => !empty($reason['strong']));
            if (!$strong) {
                continue;
            }
            $id = (int)$candidate['id'];
            if ($candidate['type'] === CrmMatcher::TYPE_COMPANY) {
                $companies[] = $id;
                $mains[] = $access->mainResponsibleId(CmsCrmAccessRequest::RECORD_COMPANY, $id);
            } elseif ($candidate['type'] === CrmMatcher::TYPE_CLIENT) {
                $clients[] = $id;
                $main = $access->mainResponsibleId(CmsCrmAccessRequest::RECORD_CLIENT, $id);
                if ($main === null) {
                    // A person of a company: its main responsible.
                    foreach ((new Query())->from('{{%cms_company2user}}')->andWhere(['cms_user_id' => $id])->select('cms_company_id')->column() as $companyId) {
                        $mains[] = $access->mainResponsibleId(CmsCrmAccessRequest::RECORD_COMPANY, (int)$companyId);
                    }
                } else {
                    $mains[] = $main;
                }
            }
        }
        $mains = array_values(array_unique($mains, SORT_REGULAR));
        if (count($mains) !== 1 || $mains[0] === null) {
            return null;
        }
        $mainId = (int)$mains[0];
        if (!\Yii::$app->authManager->checkAccess($mainId, 'cms/admin-lead')) {
            return null;
        }

        return [
            'executorId' => $mainId,
            'companyId' => count(array_unique($companies)) === 1 ? $companies[0] : null,
            'clientId' => count(array_unique($clients)) === 1 ? $clients[0] : null,
        ];
    }

    /**
     * Routes an incoming request before it is saved (CmsLeadService::createFromSource()):
     * to the main responsible of the found client, or by turns to the queue.
     *
     * @return string|null 'main' | 'round' | null (stays for the queue or the old rule)
     */
    public static function route(CmsLead $lead, array $phones, array $emails): ?string
    {
        if ($lead->status !== CmsLead::STATUS_NEW || $lead->executor_id || !$lead->cms_site_id) {
            return null;
        }
        $settings = self::settings();
        $siteId = (int)$lead->cms_site_id;
        // The contacts of CmsLeadService: value strings or rows with 'value'.
        $values = static fn(array $rows) => array_values(array_filter(array_map(static fn($row) => trim((string)(is_array($row) ? ($row['value'] ?? '') : $row)), $rows)));
        $phones = $values($phones);
        $emails = $values($emails);
        if ($settings && $settings->inboundToMain && ($phones || $emails)
            && ($main = self::mainOf(['siteId' => $siteId, 'phones' => $phones, 'emails' => $emails, 'types' => [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT]]))) {
            $lead->executor_id = $main['executorId'];
            $lead->status = CmsLead::STATUS_IN_WORK;
            if ($main['companyId'] && !$lead->cms_company_id) {
                $lead->cms_company_id = $main['companyId'];
            }
            if ($main['clientId'] && !$lead->cms_user_id) {
                $lead->cms_user_id = $main['clientId'];
            }

            return 'main';
        }
        // The queue of the pipeline the request lands in (its source, else the default one).
        if (!$lead->cms_lead_pipeline_id && ($pipeline = CmsLeadPipeline::forSource($siteId, (string)$lead->source_type))) {
            $lead->cms_lead_pipeline_id = (int)$pipeline->id;
        }
        $queue = self::queueOfLead($lead);
        if ($queue['mode'] === self::MODE_ROUND && ($next = self::next($siteId, $queue['key'], $queue['members']))) {
            $lead->executor_id = $next;

            return 'round';
        }

        return null;
    }

    /** After the deal routed to the main responsible is saved: the level by the rule of a new responsible, the feed. */
    public static function afterRoute(CmsLead $lead, ?string $route): void
    {
        if ($route !== 'main') {
            return;
        }
        CrmAccess::instance()->grantLeadExecutorLevel($lead);
        $executor = CmsUser::findOne((int)$lead->executor_id);
        $lead->addSystemActivity('Клиент найден в CRM: сделка сразу передана его основному ответственному — '
            .Html::encode($executor ? (string)$executor->shortDisplayName : '№'.(int)$lead->executor_id).'.');
    }
}
