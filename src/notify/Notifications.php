<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

use skeeks\cms\models\CmsNotifyProfile;
use skeeks\cms\models\CmsNotifySetting;
use skeeks\cms\models\CmsWebNotify;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\db\ActiveRecord;

/**
 * One mechanism of notifications for employees and clients (step У4).
 *
 * A notification is an event of a registered kind for a recipient. The kind
 * gives its category (sections of the bell and rows of the settings),
 * importance and who may get it. Every notification is a row of the CRM bell
 * (CmsWebNotify); other channels (mail, messengers, the application) are
 * registered by packages and deliver on EVENT_CREATED by the person's
 * settings "что и куда".
 *
 * Packages register categories, kinds and channels through configuration,
 * merged with the core ones:
 *
 * ```php
 * 'notifications' => [
 *     'kinds' => ['proposal.opened' => ['name' => 'Клиент открыл предложение', 'category' => 'clients']],
 *     'channels' => ['max' => ['class' => MaxNotifyChannel::class]],
 * ],
 * ```
 *
 * and notify:
 *
 * ```php
 * Yii::$app->notifications->notify($userId, 'proposal.opened', ['name' => '…', 'model' => $proposal]);
 * Yii::$app->notifications->group('proposal-send:'.$send->id, function () { … });
 * ```
 *
 * A row created directly (`new CmsWebNotify()`) still works: it gets the
 * category of its record (modelCategories) or "Прочее" and stays in the CRM.
 *
 * @property array           $categoryList code => config, sorted
 * @property array           $kindList     code => config
 * @property NotifyChannel[] $channelList  code => channel available on the site, the CRM first
 */
class Notifications extends Component
{
    const EVENT_CREATED = 'created';

    /** Extra blocks of the personal settings page (a delivery journal). */
    const EVENT_RENDER_SETTINGS = 'renderSettings';

    const CHANNEL_CRM = 'crm';

    const IMPORTANCE_NORMAL = 0;
    const IMPORTANCE_IMPORTANT = 1;
    const IMPORTANCE_URGENT = 2;

    const MODE_ALL = 'all';
    const MODE_IMPORTANT = 'important';
    const MODE_OFF = 'off';
    const MODE_MUTE = 'mute';

    const AUDIENCE_WORKER = 'worker';
    const AUDIENCE_CLIENT = 'client';
    const AUDIENCE_ANY = 'any';

    const CATEGORY_TASKS = 'tasks';
    const CATEGORY_CLIENTS = 'clients';
    const CATEGORY_MESSAGES = 'messages';
    const CATEGORY_FINANCE = 'finance';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_OTHER = 'other';

    /** @var array code => ['name' => ..., 'priority' => ...] */
    public $categories = [];

    /**
     * code => [
     *     'name' => label in settings,
     *     'category' => ...,
     *     'importance' => 0|1|2,
     *     'audience' => worker|client|any,
     *     'mutable' => bool (default true; urgent kinds never are),
     *     'dealArea' => access area of a deal a client needs (step У4b),
     * ]
     *
     * @var array
     */
    public $kinds = [];

    /** @var array code => class name or Yii config of a NotifyChannel */
    public $channels = [];

    /** @var array model class => category of a row created without a kind */
    public $modelCategories = [];

    /** @var array code => ['name' => ..., 'modes' => [channel => mode]] — applied to every category */
    public $presets = [];

    /** Read notifications older than this are removed by the cleanup agent. */
    public $retentionDays = 90;

    /** @var array|null */
    private $_categoryList;

    /** @var array|null */
    private $_kindList;

    /** @var NotifyChannel[]|null */
    private $_channelList;

    /** @var string[] keys of open group() calls */
    private $_groups = [];

    /** @var array user id => settings rows */
    private $_settings = [];

    public static function coreCategories(): array
    {
        return [
            self::CATEGORY_TASKS => ['name' => 'Задачи', 'priority' => 100],
            self::CATEGORY_CLIENTS => ['name' => 'Клиенты и предложения', 'priority' => 200],
            self::CATEGORY_MESSAGES => ['name' => 'Почта и чаты', 'priority' => 300],
            self::CATEGORY_FINANCE => ['name' => 'Счета и платежи', 'priority' => 400],
            self::CATEGORY_SYSTEM => ['name' => 'Система', 'priority' => 500],
            self::CATEGORY_OTHER => ['name' => 'Прочее', 'priority' => 900],
        ];
    }

    public static function coreKinds(): array
    {
        $task = ['category' => self::CATEGORY_TASKS, 'audience' => self::AUDIENCE_ANY];

        return [
            'task.created' => ['name' => 'Новая задача'] + $task,
            'task.passed' => ['name' => 'Вам передали задачу', 'importance' => self::IMPORTANCE_IMPORTANT] + $task,
            'task.check' => ['name' => 'Нужно проверить задачу'] + $task,
            'task.ready' => ['name' => 'Задача готова'] + $task,
            'task.canceled' => ['name' => 'Задача отменена'] + $task,
            'task.resumed' => ['name' => 'Задача возобновлена'] + $task,
            'task.comment' => ['name' => 'Комментарий к задаче'] + $task,
            'task.order' => ['name' => 'Изменён порядок ваших задач', 'audience' => self::AUDIENCE_WORKER] + $task,
            'task.client_unassigned' => ['name' => 'Неразобранная задача от клиента', 'importance' => self::IMPORTANCE_IMPORTANT, 'audience' => self::AUDIENCE_WORKER] + $task,
            'lead.new' => ['name' => 'Новая сделка в Неразобранном', 'category' => self::CATEGORY_CLIENTS, 'importance' => self::IMPORTANCE_IMPORTANT, 'audience' => self::AUDIENCE_WORKER],
            'lead.delegated' => ['name' => 'Сделку по вашему клиенту ведёт другой сотрудник', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_WORKER],
            'lead.merged' => ['name' => 'В вашу сделку добавлено обращение', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_WORKER],
            'crm.access_request' => ['name' => 'Запрос доступа к клиенту', 'category' => self::CATEGORY_CLIENTS, 'importance' => self::IMPORTANCE_IMPORTANT, 'audience' => self::AUDIENCE_WORKER],
            'crm.access_decided' => ['name' => 'Решение по вашему запросу доступа', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_WORKER],
            'crm.duplicate_mark' => ['name' => 'Возможный дубль клиента', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_WORKER],
            'crm.client_call' => ['name' => 'Звонок вашего клиента', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_WORKER],
            'lead.assigned' => ['name' => 'Вам назначена сделка', 'category' => self::CATEGORY_CLIENTS, 'importance' => self::IMPORTANCE_IMPORTANT, 'audience' => self::AUDIENCE_WORKER],
            'lead.partner_comment' => ['name' => 'Комментарий партнёра по сделке', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_WORKER],
            'lead.partner_update' => ['name' => 'Партнёрская заявка: статус и комментарии', 'category' => self::CATEGORY_CLIENTS, 'audience' => self::AUDIENCE_CLIENT],
        ];
    }

    public static function corePresets(): array
    {
        return [
            'crm' => ['name' => 'Всё только в вебе', 'modes' => [self::CHANNEL_CRM => self::MODE_ALL]],
        ];
    }

    public function getCategoryList(): array
    {
        if ($this->_categoryList === null) {
            $list = array_replace_recursive(self::coreCategories(), (array)$this->categories);
            uasort($list, static fn($a, $b) => ((int)($a['priority'] ?? 500)) <=> ((int)($b['priority'] ?? 500)));
            $this->_categoryList = $list;
        }

        return $this->_categoryList;
    }

    public function getKindList(): array
    {
        if ($this->_kindList === null) {
            $list = [];
            foreach (array_replace_recursive(self::coreKinds(), (array)$this->kinds) as $code => $kind) {
                $kind = array_merge([
                    'name' => $code,
                    'category' => self::CATEGORY_OTHER,
                    'importance' => self::IMPORTANCE_NORMAL,
                    'audience' => self::AUDIENCE_WORKER,
                    'mutable' => true,
                    'dealArea' => null,
                ], (array)$kind);
                if ((int)$kind['importance'] >= self::IMPORTANCE_URGENT) {
                    $kind['mutable'] = false;
                }
                $list[$code] = $kind;
            }
            $this->_kindList = $list;
        }

        return $this->_kindList;
    }

    public function kind(string $code): ?array
    {
        return $this->getKindList()[$code] ?? null;
    }

    public function categoryName(?string $code): string
    {
        return (string)($this->getCategoryList()[$code ?: self::CATEGORY_OTHER]['name'] ?? $code);
    }

    /** @return NotifyChannel[] */
    public function getChannelList(): array
    {
        if ($this->_channelList === null) {
            $list = [self::CHANNEL_CRM => new CrmNotifyChannel()];
            foreach ((array)$this->channels as $code => $config) {
                if (!$config || $code === self::CHANNEL_CRM) {
                    continue;
                }
                $config = is_string($config) ? ['class' => $config] : $config;
                $object = \Yii::createObject(array_merge($config, is_subclass_of($config['class'] ?? '', NotifyChannelProviderInterface::class) ? [] : ['code' => $code]));
                // A provider gives several channels at once (one per installed messenger).
                $channels = $object instanceof NotifyChannelProviderInterface ? $object->channels() : [$code => $object];
                foreach ($channels as $channelCode => $channel) {
                    /** @var NotifyChannel $channel */
                    if ($channelCode !== self::CHANNEL_CRM && !isset($list[$channelCode]) && $channel->isAvailable()) {
                        $list[$channelCode] = $channel;
                    }
                }
            }
            uasort($list, static fn(NotifyChannel $a, NotifyChannel $b) => $a->priority <=> $b->priority);
            $this->_channelList = $list;
        }

        return $this->_channelList;
    }

    /** Channels are built once a request; a long process (a test) asks again after a bot appears. */
    public function resetChannels(): void
    {
        $this->_channelList = null;
    }

    public function channel(string $code): ?NotifyChannel
    {
        return $this->getChannelList()[$code] ?? null;
    }

    /** Ready variants whose channels exist on the site. */
    public function presetList(): array
    {
        $result = [];
        foreach (array_replace(self::corePresets(), (array)$this->presets) as $code => $preset) {
            if (!$preset) {
                continue;
            }
            foreach (array_keys((array)$preset['modes']) as $channel) {
                if (!$this->channel($channel)) {
                    continue 2;
                }
            }
            $result[$code] = $preset;
        }

        return $result;
    }

    /**
     * Everything notified inside the callback belongs to one group: the bell
     * shows it as one entry, messengers get one message.
     */
    public function group(string $key, callable $callback)
    {
        $this->_groups[] = mb_substr($key, 0, 128);
        try {
            return $callback();
        } finally {
            array_pop($this->_groups);
        }
    }

    public function currentGroup(): ?string
    {
        return $this->_groups ? end($this->_groups) : null;
    }

    /**
     * Notifies recipients of an event.
     *
     * @param int|int[] $userIds
     * @param array     $data name (required), comment, url, model (a record) or model_code + model_id,
     *                        eventKey (one event is stored once per recipient), collapse (an unread row
     *                        with the same eventKey takes the new text and counts one more event),
     *                        groupKey (otherwise the open group()), importance (overrides the kind)
     * @return CmsWebNotify[] stored rows (new or updated), muted recipients and repeats skipped
     */
    public function notify($userIds, string $kind, array $data): array
    {
        $config = $this->kind($kind);
        if (!$config) {
            throw new InvalidArgumentException("Неизвестный вид уведомления «{$kind}».");
        }
        if (trim((string)($data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Уведомлению нужен текст.');
        }
        $importance = (int)($data['importance'] ?? $config['importance']);
        [$modelCode, $modelId] = $this->modelOf($data);
        $eventKey = isset($data['eventKey']) && $data['eventKey'] !== '' ? mb_substr((string)$data['eventKey'], 0, 128) : null;
        $groupKey = isset($data['groupKey']) && $data['groupKey'] !== '' ? mb_substr((string)$data['groupKey'], 0, 128) : $this->currentGroup();

        $result = [];
        foreach (array_unique(array_filter(array_map('intval', (array)$userIds))) as $userId) {
            // Settings are read anew for every call: a long process (a queue worker) sees changes at once.
            $this->resetSettings($userId);
            if ($importance < self::IMPORTANCE_URGENT && $config['mutable'] && $this->isMuted($userId, $kind)) {
                continue;
            }
            $silent = $importance < self::IMPORTANCE_URGENT
                && !self::allows($this->mode($userId, $kind, self::CHANNEL_CRM), $importance);

            $notify = null;
            $isNew = true;
            if ($eventKey !== null) {
                $notify = CmsWebNotify::find()->andWhere(['cms_user_id' => $userId, 'event_key' => $eventKey])->one();
                if ($notify && (!($data['collapse'] ?? false))) {
                    continue;
                }
                // A burst read by the person is history: the next event starts a new row. A silent row is
                // read from the start (the category is off in the CRM), so it keeps collapsing.
                if ($notify && $notify->is_read && !$silent) {
                    $notify->updateAttributes(['event_key' => null]);
                    $notify = null;
                }
                if ($notify) {
                    $isNew = false;
                    $notify->count = (int)$notify->count + 1;
                }
            }
            if (!$notify) {
                $notify = new CmsWebNotify([
                    'cms_user_id' => $userId,
                    'kind' => $kind,
                    'category' => (string)$config['category'],
                    'event_key' => $eventKey,
                    'group_key' => $groupKey,
                    'count' => 1,
                ]);
            }
            $notify->importance = max($importance, (int)$notify->importance);
            $notify->name = mb_substr((string)$data['name'], 0, 250);
            $notify->comment = isset($data['comment']) && $data['comment'] !== '' ? mb_substr((string)$data['comment'], 0, 2000) : null;
            $notify->url = isset($data['url']) && $data['url'] !== '' ? (string)$data['url'] : null;
            $notify->model_code = $modelCode;
            $notify->model_id = $modelId;
            $notify->is_read = $silent ? 1 : 0;
            $notify->is_seen = $silent ? 1 : 0;
            $notify->updated_at = time();
            try {
                $saved = $notify->save();
            } catch (\yii\db\IntegrityException $e) {
                // Another process stored the same event (eventKey) between the check and the insert:
                // the event is there, the caller's operation goes on.
                continue;
            }
            if (!$saved) {
                \Yii::error('Уведомление «'.$kind.'» пользователю '.$userId.' не сохранено: '.implode(' ', $notify->getFirstErrors()), self::class);
                continue;
            }
            $this->trigger(self::EVENT_CREATED, new NotifyEvent(['notify' => $notify, 'isNew' => $isNew]));
            $result[] = $notify;
        }

        return $result;
    }

    /** Mode of a kind in a channel for the person: the kind's own row, else its category's, else the default. */
    public function mode(int $userId, string $kind, string $channel): string
    {
        $settings = $this->settings($userId);
        $category = (string)($this->kind($kind)['category'] ?? self::CATEGORY_OTHER);

        return $settings[CmsNotifySetting::SCOPE_KIND][$kind][$channel]
            ?? $settings[CmsNotifySetting::SCOPE_CATEGORY][$category][$channel]
            ?? ($channel === self::CHANNEL_CRM ? self::MODE_ALL : self::MODE_OFF);
    }

    /** The mode set for the kind itself, null — it follows its category. */
    public function kindMode(int $userId, string $kind, string $channel): ?string
    {
        $mode = $this->settings($userId)[CmsNotifySetting::SCOPE_KIND][$kind][$channel] ?? null;

        return $mode === self::MODE_MUTE ? null : $mode;
    }

    public function categoryMode(int $userId, string $category, string $channel): string
    {
        return $this->settings($userId)[CmsNotifySetting::SCOPE_CATEGORY][$category][$channel]
            ?? ($channel === self::CHANNEL_CRM ? self::MODE_ALL : self::MODE_OFF);
    }

    /** "Не уведомлять о таком". */
    public function isMuted(int $userId, string $kind): bool
    {
        return ($this->settings($userId)[CmsNotifySetting::SCOPE_KIND][$kind]['*'] ?? null) === self::MODE_MUTE;
    }

    public static function allows(string $mode, int $importance): bool
    {
        return $mode === self::MODE_ALL || ($mode === self::MODE_IMPORTANT && $importance >= self::IMPORTANCE_IMPORTANT);
    }

    /** Does a notification go to the channel? Urgent ones ignore nothing but an explicit "off" of a channel. */
    public function shouldDeliver(CmsWebNotify $notify, string $channel): bool
    {
        if (!$notify->kind) {
            return $channel === self::CHANNEL_CRM;
        }

        return self::allows($this->mode((int)$notify->cms_user_id, (string)$notify->kind, $channel), (int)$notify->importance);
    }

    /** Kinds the person switched off with "не уведомлять о таком". */
    public function mutedKinds(int $userId): array
    {
        $result = [];
        foreach ($this->settings($userId)[CmsNotifySetting::SCOPE_KIND] ?? [] as $kind => $channels) {
            if (($channels['*'] ?? null) === self::MODE_MUTE) {
                $result[] = $kind;
            }
        }

        return $result;
    }

    public function setMode(int $userId, string $scope, string $code, string $channel, ?string $mode): void
    {
        $where = ['cms_user_id' => $userId, 'scope' => $scope, 'code' => $code, 'channel' => $channel];
        $row = CmsNotifySetting::findOne($where);
        if ($mode === null) {
            if ($row) {
                $row->delete();
            }
        } else {
            $row = $row ?: new CmsNotifySetting($where);
            $row->mode = $mode;
            $row->save(false);
        }
        unset($this->_settings[$userId]);
    }

    public function mute(int $userId, string $kind, bool $mute = true): bool
    {
        $config = $this->kind($kind);
        if ($mute && (!$config || !$config['mutable'])) {
            return false;
        }
        $this->setMode($userId, CmsNotifySetting::SCOPE_KIND, $kind, '*', $mute ? self::MODE_MUTE : null);

        return true;
    }

    /** Applies a ready variant to every category; muted kinds stay muted. */
    public function applyPreset(int $userId, string $code): bool
    {
        $preset = $this->presetList()[$code] ?? null;
        if (!$preset) {
            return false;
        }
        $db = CmsNotifySetting::getDb();
        $db->transaction(function () use ($userId, $code, $preset) {
            CmsNotifySetting::deleteAll(['cms_user_id' => $userId, 'scope' => CmsNotifySetting::SCOPE_CATEGORY]);
            CmsNotifySetting::deleteAll(['and', ['cms_user_id' => $userId, 'scope' => CmsNotifySetting::SCOPE_KIND], ['<>', 'channel', '*']]);
            foreach (array_keys($this->getCategoryList()) as $category) {
                foreach ($this->getChannelList() as $channel => $object) {
                    $mode = $preset['modes'][$channel] ?? self::MODE_OFF;
                    if ($mode !== ($channel === self::CHANNEL_CRM ? self::MODE_ALL : self::MODE_OFF)) {
                        $this->setMode($userId, CmsNotifySetting::SCOPE_CATEGORY, $category, $channel, $mode);
                    }
                }
            }
            $profile = $this->profile($userId);
            $profile->preset = $code;
            $profile->save(false);
        });
        unset($this->_settings[$userId]);

        return true;
    }

    public function profile(int $userId): CmsNotifyProfile
    {
        return CmsNotifyProfile::findOne($userId) ?: new CmsNotifyProfile(['cms_user_id' => $userId, 'is_quiet' => 0, 'quiet_from' => 21 * 60, 'quiet_to' => 9 * 60]);
    }

    /**
     * When the person's quiet hours end, if $now falls into them; null — not quiet now.
     * Urgent notifications do not wait (the caller checks importance).
     */
    public function quietEndsAt(int $userId, ?int $now = null): ?int
    {
        $profile = CmsNotifyProfile::findOne($userId);
        if (!$profile || !$profile->is_quiet || (int)$profile->quiet_from === (int)$profile->quiet_to) {
            return null;
        }
        $now = $now ?? time();
        $from = (int)$profile->quiet_from;
        $to = (int)$profile->quiet_to;
        $midnight = (new \DateTimeImmutable('@'.$now))->setTimezone(new \DateTimeZone(\Yii::$app->timeZone))->setTime(0, 0)->getTimestamp();
        $minute = intdiv($now - $midnight, 60);
        if ($from < $to) {
            return $minute >= $from && $minute < $to ? $midnight + $to * 60 : null;
        }
        if ($minute >= $from) {
            return $midnight + 86400 + $to * 60;
        }

        return $minute < $to ? $midnight + $to * 60 : null;
    }

    public function resetSettings(?int $userId = null): void
    {
        if ($userId === null) {
            $this->_settings = [];
        } else {
            unset($this->_settings[$userId]);
        }
    }

    /** Audience of a person: an employee or a client. */
    public function audienceOf(int $userId): string
    {
        $isWorker = (int)\skeeks\cms\models\CmsUser::find()->select('is_worker')->andWhere(['id' => $userId])->scalar();

        return $isWorker ? self::AUDIENCE_WORKER : self::AUDIENCE_CLIENT;
    }

    /**
     * Categories with the kinds a person of the audience may get:
     * code => ['name' => ..., 'kinds' => [code => kind]].
     */
    public function settingsTree(string $audience): array
    {
        $result = [];
        foreach ($this->getKindList() as $code => $kind) {
            if ($kind['audience'] !== self::AUDIENCE_ANY && $kind['audience'] !== $audience) {
                continue;
            }
            $result[$kind['category']]['kinds'][$code] = $kind;
        }
        $sorted = [];
        foreach ($this->getCategoryList() as $code => $category) {
            if (isset($result[$code])) {
                $sorted[$code] = ['name' => $category['name'], 'kinds' => $result[$code]['kinds']];
            }
        }

        return $sorted;
    }

    /**
     * Saves the table "что и куда" of the settings page: modes of categories
     * (modes[category][channel]) and of single kinds (kinds[kind][channel], "" —
     * as the category). A manual change leaves the ready variant.
     */
    public function saveModes(int $userId, array $modes, array $kinds = []): void
    {
        $valid = [self::MODE_ALL, self::MODE_IMPORTANT, self::MODE_OFF];
        $channels = $this->getChannelList();
        $tree = $this->settingsTree($this->audienceOf($userId));
        CmsNotifySetting::getDb()->transaction(function () use ($userId, $modes, $kinds, $valid, $channels, $tree) {
            foreach ($tree as $category => $data) {
                foreach ($channels as $channel => $object) {
                    $mode = (string)($modes[$category][$channel] ?? '');
                    if (!in_array($mode, $valid, true)) {
                        continue;
                    }
                    $default = $channel === self::CHANNEL_CRM ? self::MODE_ALL : self::MODE_OFF;
                    $this->setMode($userId, CmsNotifySetting::SCOPE_CATEGORY, $category, $channel, $mode === $default ? null : $mode);
                }
                foreach (array_keys($data['kinds']) as $kind) {
                    foreach ($channels as $channel => $object) {
                        if (!isset($kinds[$kind]) || !array_key_exists($channel, (array)$kinds[$kind])) {
                            continue;
                        }
                        $mode = (string)$kinds[$kind][$channel];
                        $this->setMode($userId, CmsNotifySetting::SCOPE_KIND, $kind, $channel, in_array($mode, $valid, true) ? $mode : null);
                    }
                }
            }
            $profile = $this->profile($userId);
            $profile->preset = null;
            $profile->save(false);
        });
        $this->resetSettings($userId);
    }

    /** "Всё, что идёт в CRM, слать и сюда": the channel column repeats the CRM column. */
    public function copyCrm(int $userId, string $channel): bool
    {
        if ($channel === self::CHANNEL_CRM || !$this->channel($channel)) {
            return false;
        }
        $modes = [];
        $kinds = [];
        foreach ($this->settingsTree($this->audienceOf($userId)) as $category => $data) {
            $modes[$category][$channel] = $this->categoryMode($userId, $category, self::CHANNEL_CRM);
            // A kind with its own CRM mode gets the same here; others follow the category ("" clears).
            foreach (array_keys($data['kinds']) as $kind) {
                $kinds[$kind][$channel] = (string)$this->kindMode($userId, $kind, self::CHANNEL_CRM);
            }
        }
        $this->saveModes($userId, $modes, $kinds);

        return true;
    }

    /** Category of a row created without a kind. */
    public function categoryOfModel(?string $modelCode): string
    {
        $map = array_merge([
            \skeeks\cms\models\CmsTask::class => self::CATEGORY_TASKS,
            \skeeks\cms\models\CmsLead::class => self::CATEGORY_CLIENTS,
            'skeeks\cms\shop\models\ShopPartnerPayout' => self::CATEGORY_FINANCE,
            'skeeks\cms\shop\models\ShopBonusTransaction' => self::CATEGORY_FINANCE,
        ], (array)$this->modelCategories);

        return $modelCode && isset($map[$modelCode]) ? (string)$map[$modelCode] : self::CATEGORY_OTHER;
    }

    /** Removes read notifications older than retentionDays; returns how many. */
    public function cleanup(?int $now = null): int
    {
        if ((int)$this->retentionDays < 1) {
            return 0;
        }

        return CmsWebNotify::deleteAll(['and', ['is_read' => 1], ['<', 'created_at', ($now ?? time()) - (int)$this->retentionDays * 86400]]);
    }

    private function modelOf(array $data): array
    {
        $model = $data['model'] ?? null;
        if ($model instanceof ActiveRecord) {
            $code = $model->canGetProperty('skeeksModelCode') ? (string)$model->skeeksModelCode : get_class($model);

            return [$code, (int)$model->getPrimaryKey()];
        }
        if (!empty($data['model_code'])) {
            return [(string)$data['model_code'], isset($data['model_id']) ? (int)$data['model_id'] : null];
        }

        return [null, null];
    }

    private function settings(int $userId): array
    {
        if (!isset($this->_settings[$userId])) {
            $result = [];
            foreach (CmsNotifySetting::find()->andWhere(['cms_user_id' => $userId])->asArray()->all() as $row) {
                $result[$row['scope']][$row['code']][$row['channel']] = $row['mode'];
            }
            $this->_settings[$userId] = $result;
        }

        return $this->_settings[$userId];
    }
}
