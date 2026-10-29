<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCrmAccessLog;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;

/**
 * The journal of the access to clients (owner's decision 29.10.2026): who
 * got what access to which client, when and on what ground — kept for good
 * (cms_crm_access_log, the requests made before it copied in). Every event is
 * also a record of the client feed (log type `crm_access`).
 *
 * Who sees what (visibleCondition()): an administrator — everything; a leader
 * — the events of the clients of his and his subordinates', and theirs; a main
 * responsible — of his clients; an employee — his own events.
 */
class CrmAccessLog
{
    public const LOG_TYPE = 'crm_access';

    public const REQUEST = 'request';
    public const REQUEST_APPROVED = 'request_approved';
    public const REQUEST_DECLINED = 'request_declined';
    public const REQUEST_CANCELLED = 'request_cancelled';
    public const REQUEST_OBSOLETE = 'request_obsolete';
    public const WORK = 'work';
    /** Historical: «Взять в работу» of the general pool, removed (п. 13а); kept to show the old journal rows. */
    public const TAKE = 'take';
    public const GRANT = 'grant';
    public const LEVEL = 'level';
    public const REMOVE = 'remove';
    public const TRANSFER = 'transfer';
    public const MAIN_AUTO = 'main_auto';
    /** Historical: «Вернуть в общий пул», removed (п. 13а); kept to show the old journal rows. */
    public const POOL = 'pool';
    public const PROTECTION = 'protection';

    public static function events(): array
    {
        return [
            self::REQUEST => 'Запрос на работу с клиентом',
            self::REQUEST_APPROVED => 'Запрос одобрен',
            self::REQUEST_DECLINED => 'Запрос отклонён',
            self::REQUEST_CANCELLED => 'Запрос отменён',
            self::REQUEST_OBSOLETE => 'Запрос устарел',
            self::WORK => 'Работа по своей сделке',
            self::TAKE => 'Взят в работу из общего пула',
            self::GRANT => 'Доступ выдан',
            self::LEVEL => 'Уровень изменён',
            self::REMOVE => 'Доступ убран',
            self::TRANSFER => 'Клиент передан',
            self::MAIN_AUTO => 'Основной ответственный назначен',
            self::POOL => 'Возвращён в общий пул',
            self::PROTECTION => 'Защита изменена',
        ];
    }

    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $ready = CmsCrmAccessLog::getDb()->getTableSchema(CmsCrmAccessLog::tableName(), true) !== null;
        }

        return $ready;
    }

    /**
     * Writes an event and its record in the client feed. Never breaks the
     * action it describes.
     *
     * @param array $data actor_id, user_id, level_from, level_to, cms_lead_id, shop_payment_id, request_id, comment
     */
    public static function write(string $event, string $recordCode, int $recordId, array $data = []): ?CmsCrmAccessLog
    {
        if (!self::ready() || $recordId <= 0) {
            return null;
        }
        try {
            $log = new CmsCrmAccessLog(array_merge([
                'created_at' => time(),
                'cms_site_id' => \Yii::$app->has('skeeks') && \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null,
                'event' => $event,
                'record_code' => $recordCode,
                'record_id' => $recordId,
                'actor_id' => \Yii::$app->has('user') && \Yii::$app->user->id ? (int)\Yii::$app->user->id : null,
            ], array_intersect_key($data, array_flip(['actor_id', 'user_id', 'level_from', 'level_to', 'cms_lead_id', 'shop_payment_id', 'request_id', 'comment']))));
            if ($log->comment !== null) {
                $log->comment = mb_substr((string)$log->comment, 0, 500);
            }
            if (!$log->save()) {
                \Yii::warning('Событие доступа не записано: '.implode('; ', $log->getFirstErrors()), self::class);

                return null;
            }
            self::feed($log);

            return $log;
        } catch (\Throwable $e) {
            \Yii::warning('Событие доступа не записано: '.$e->getMessage(), self::class);

            return null;
        }
    }

    /**
     * The level of an event «было → стало»: «Свои сделки → Полный»; a new link
     * (GRANT, WORK) without a previous level is «нет доступа → Свои сделки».
     */
    public static function levelChange(CmsCrmAccessLog $log): string
    {
        $levels = CrmAccess::levels();
        $name = static fn($code) => $code ? ($levels[$code] ?? $code) : '';
        $from = $name($log->level_from);
        if ($from === '' && $log->level_to && in_array($log->event, [self::GRANT, self::WORK], true)) {
            $from = 'нет доступа';
        }

        return trim($from.($from !== '' || $log->level_to ? ' → ' : '').$name($log->level_to), ' →');
    }

    /** «Петрова Анна: доступ выдан — Смирнов Олег: нет доступа → Свои сделки» — the event in words, without a grammatical gender. */
    public static function text(CmsCrmAccessLog $log): string
    {
        $name = static fn($id) => $id && ($user = CmsUser::findOne((int)$id)) ? (string)$user->shortDisplayName : '';
        $levels = CrmAccess::levels();
        $level = static fn($code) => $code ? '«'.($levels[$code] ?? $code).'»' : '';
        $actor = $name($log->actor_id);
        $user = $name($log->user_id);
        switch ($log->event) {
            case self::REQUEST:
                $text = 'запрос на работу с клиентом'.($log->level_to ? ', уровень '.$level($log->level_to) : '');
                break;
            case self::REQUEST_APPROVED:
                $text = 'запрос одобрен'.($user && $user !== $actor ? ' — '.$user : '').($log->level_to ? ', уровень '.$level($log->level_to) : '');
                break;
            case self::REQUEST_DECLINED:
                $text = 'запрос отклонён'.($user && $user !== $actor ? ' — '.$user : '');
                break;
            case self::REQUEST_CANCELLED:
                $text = 'запрос отменён';
                break;
            case self::REQUEST_OBSOLETE:
                $text = 'запрос закрыт как устаревший';
                break;
            case self::WORK:
                $text = 'работа с клиентом по своей сделке, уровень '.$level($log->level_to ?: CrmAccess::LEVEL_DEAL);
                break;
            case self::TAKE:
                $text = 'клиент взят в работу из общего пула';
                break;
            case self::GRANT:
                $text = 'доступ выдан — '.$user.': '.self::levelChange($log);
                break;
            case self::LEVEL:
                $text = 'уровень изменён — '.$user.': '.$level($log->level_from).' → '.$level($log->level_to);
                break;
            case self::REMOVE:
                $text = 'доступ убран — '.$user;
                break;
            case self::TRANSFER:
                $text = 'клиент передан, основной ответственный — '.$user;
                break;
            case self::MAIN_AUTO:
                $text = 'основной ответственный назначен — '.$user;
                break;
            case self::POOL:
                $text = 'клиент возвращён в общий пул';
                break;
            case self::PROTECTION:
                $text = 'защита клиента изменена';
                break;
            default:
                $text = self::events()[$log->event] ?? $log->event;
        }
        $who = in_array($log->event, [self::WORK, self::TAKE], true) ? ($user ?: $actor) : $actor;

        return ($who !== '' ? $who.': ' : '').$text.($log->comment ? ' ('.$log->comment.')' : '');
    }

    /**
     * The condition of the events the user sees (null — all, an administrator).
     *
     * @return array|null a where condition for CmsCrmAccessLog::find()
     */
    public static function visibleCondition($user): ?array
    {
        $access = CrmAccess::instance();
        if ($access->isAdmin($user)) {
            return null;
        }
        $userId = is_object($user) ? (int)$user->id : (int)$user;
        $ids = array_values(array_unique(array_merge([$userId], array_map('intval', CmsDepartment::subordinateIds($userId)))));
        $table = CmsCrmAccessLog::tableName();
        $main = static fn(string $class, string $recordColumn, string $userColumn, string $code) => ['and', [$table.'.record_code' => $code],
            ['exists', $class::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($table.'.record_id'), 'is_main' => 1, $userColumn => $ids])]];

        return ['or',
            [$table.'.actor_id' => $ids],
            [$table.'.user_id' => $ids],
            $main(CmsCompany2manager::class, 'cms_company_id', 'cms_user_id', CmsCrmAccessRequest::RECORD_COMPANY),
            $main(CmsUser2manager::class, 'client_id', 'worker_id', CmsCrmAccessRequest::RECORD_CLIENT),
        ];
    }

    /** Requests waiting for the user's decision (the filter «Ждут моего решения», the counter of «Компании»). */
    public static function waitingCondition($user): array
    {
        $table = CmsCrmAccessLog::tableName();

        return ['and', [$table.'.event' => self::REQUEST],
            [$table.'.request_id' => CmsCrmAccessRequest::find()->select(CmsCrmAccessRequest::tableName().'.id')
                ->andWhere(CrmAccess::instance()->requestsCondition($user, CrmAccess::REQUESTS_TO_ME))]];
    }

    /** The record of the event in the client feed. */
    private static function feed(CmsCrmAccessLog $log): void
    {
        $record = $log->getRecord();
        if (!$record) {
            return;
        }
        $entry = new CmsLog([
            'model_code' => $record->skeeksModelCode,
            'model_id' => (int)$record->id,
            'log_type' => self::LOG_TYPE,
            'comment' => self::text($log),
            // The deal of the event (a change of its responsible): the feed links it.
            'data' => array_filter(['access_log_id' => (int)$log->id, 'event' => $log->event, 'cms_lead_id' => (int)$log->cms_lead_id ?: null]),
        ]);
        if ($record instanceof CmsCompany && $entry->hasAttribute('cms_company_id')) {
            $entry->cms_company_id = (int)$record->id;
        }
        $entry->save(false);
    }
}
