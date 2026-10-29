<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDepartment2worker;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use yii\db\Query;
use yii\helpers\Html;

/**
 * «Входящие заявки» (owner's decision 29.10.2026, cms-communication
 * ARCHITECTURE.md п. 13б): who sorts the requests of «Неразобранное» —
 * employees and departments of the CRM settings (CrmSettings::$inboundWorkerIds,
 * $inboundDepartmentIds) — and how:
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

    /** @var int[][] site id => members, per request */
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

    public static function mode(): string
    {
        $settings = self::settings();

        return $settings && $settings->inboundMode === self::MODE_ROUND ? self::MODE_ROUND : self::MODE_ALL;
    }

    /** The queue is set: the employees or the departments of «Входящие заявки». */
    public static function configured(?int $siteId = null): bool
    {
        return (bool)self::memberIds($siteId);
    }

    /**
     * Active employees of the queue with the deals right, ordered by id (the
     * order of the turn).
     *
     * @return int[]
     */
    public static function memberIds(?int $siteId = null): array
    {
        $settings = self::settings();
        if (!$settings) {
            return [];
        }
        $key = (int)$siteId;
        if (array_key_exists($key, self::$members)) {
            return self::$members[$key];
        }
        $ids = array_map('intval', (array)$settings->inboundWorkerIds);
        $departments = array_filter(array_map('intval', (array)$settings->inboundDepartmentIds));
        if ($departments) {
            $ids = array_merge($ids, array_map('intval', CmsDepartment2worker::find()
                ->andWhere(['cms_department_id' => $departments])->select('worker_id')->column()));
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

    /** The queue sees every unsorted deal (MODE_ALL); by turns only the given one sees it (as its responsible). */
    public static function seesAllUnsorted(int $userId): bool
    {
        return self::mode() === self::MODE_ALL && self::isMember($userId);
    }

    /** Forgets the members read in this request (tests, a changed setting). */
    public static function reset(): void
    {
        self::$members = [];
    }

    /**
     * Who is notified about a new unsorted deal: the queue (MODE_ALL) and the
     * administrators with the deals right (their own notification settings
     * decide). Null — no queue: the old rule (CmsLead::availableManagerIds()).
     *
     * @return int[]|null
     */
    public static function notifyIds(CmsLead $lead): ?array
    {
        $siteId = $lead->cms_site_id ? (int)$lead->cms_site_id : null;
        $members = self::memberIds($siteId);
        if (!$members) {
            return null;
        }
        $ids = self::mode() === self::MODE_ALL ? $members : [];
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
     * The next employee of the turn. Locks the row of the site, so it must run
     * in the transaction that saves the deal: a parallel request waits and gets
     * the employee after.
     */
    public static function next(int $siteId, string $queue = self::QUEUE_DEFAULT): ?int
    {
        $members = self::memberIds($siteId);
        if (!$members || !self::ready()) {
            return null;
        }
        $db = \Yii::$app->db;
        $own = $db->getTransaction() === null ? $db->beginTransaction() : null;
        try {
            $table = '{{%cms_crm_inbound_rotation}}';
            $db->createCommand('INSERT IGNORE INTO '.$db->quoteTableName($table).' (cms_site_id, queue, updated_at) VALUES (:site, :queue, :time)',
                [':site' => $siteId, ':queue' => $queue, ':time' => time()])->execute();
            $row = $db->createCommand('SELECT id, last_user_id FROM '.$db->quoteTableName($table).' WHERE cms_site_id = :site AND queue = :queue FOR UPDATE',
                [':site' => $siteId, ':queue' => $queue])->queryOne();
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
        if (self::mode() === self::MODE_ROUND && ($next = self::next($siteId))) {
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
