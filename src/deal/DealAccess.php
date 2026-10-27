<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\deal;

use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\queries\CmsDealQuery;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\Query;

/**
 * Access of client people (not employees) to deals — "Сделка как услуга".
 *
 * A deal is a service the client bought. What a person of the client may do in
 * it is counted per access area (tasks, reports, documents, billing, manage and
 * areas added by packages) as a level: 0 none, 10 view, 20 take part (comment,
 * act), 30 full (create tasks, manage). The level is the highest of:
 * - the owner of a deal without a company — full;
 * - a grant on this deal (cms_client_access with cms_deal_id);
 * - a grant on all deals of the deal's company (cms_deal_id is null);
 * and it never exceeds what is open in the deal: the deal type's areas (no rows
 * — every area) limited by the deal's own rows.
 *
 * Every answer comes from one SQL condition, so a single-record check, a list,
 * a count and the recipients of a notification agree. Employees are not checked
 * here: they keep forManager() scopes.
 *
 * Packages register areas and resource providers through configuration:
 *
 * ```php
 * 'dealAccess' => [
 *     'areas' => ['chat' => ['name' => 'Чат по услуге', 'levels' => [10 => 'Читать', 20 => 'Писать']]],
 *     'resourceProviders' => ['hosting_site' => SiteDealResourceProvider::class],
 * ],
 * ```
 *
 * @property array $areaList area => normalized config, sorted by priority
 */
class DealAccess extends Component
{
    const LEVEL_NONE = 0;
    const LEVEL_VIEW = 10;
    const LEVEL_ACT = 20;
    const LEVEL_FULL = 30;

    const AREA_TASKS = 'tasks';
    const AREA_REPORTS = 'reports';
    const AREA_DOCUMENTS = 'documents';
    const AREA_BILLING = 'billing';
    const AREA_MANAGE = 'manage';

    /**
     * Access areas: code => ['name' => ..., 'levels' => [level => label], 'hint' => ..., 'priority' => ...].
     * Registrations of packages merge with the core ones.
     *
     * @var array
     */
    public $areas = [];

    /**
     * Resource kinds of deals: type => class name or Yii config of a DealResourceProviderInterface.
     *
     * @var array
     */
    public $resourceProviders = [];

    /** @var array|null */
    private $_areaList;

    /** @var DealResourceProviderInterface[] */
    private $_providers = [];

    public static function coreAreas(): array
    {
        return [
            self::AREA_TASKS     => [
                'name'     => 'Задачи',
                'levels'   => [self::LEVEL_VIEW => 'Смотреть', self::LEVEL_ACT => 'Комментировать', self::LEVEL_FULL => 'Ставить задачи'],
                'priority' => 100,
            ],
            self::AREA_REPORTS   => [
                'name'     => 'Отчёты',
                'levels'   => [self::LEVEL_VIEW => 'Смотреть'],
                'priority' => 200,
            ],
            self::AREA_DOCUMENTS => [
                'name'     => 'Документы',
                'levels'   => [self::LEVEL_VIEW => 'Смотреть'],
                'priority' => 300,
            ],
            self::AREA_BILLING   => [
                'name'     => 'Счета и оплата',
                'levels'   => [self::LEVEL_VIEW => 'Смотреть и оплачивать'],
                'priority' => 400,
            ],
            self::AREA_MANAGE    => [
                'name'     => 'Управление услугой',
                'levels'   => [self::LEVEL_VIEW => 'Смотреть', self::LEVEL_ACT => 'Управлять'],
                'hint'     => 'Например DNS, перезапуск сервера, продление',
                'priority' => 500,
            ],
        ];
    }

    /**
     * @return array area => ['name', 'levels', 'hint', 'priority', 'max']
     */
    public function getAreaList(): array
    {
        if ($this->_areaList === null) {
            $list = [];
            foreach (array_merge(static::coreAreas(), (array)$this->areas) as $code => $config) {
                $levels = (array)($config['levels'] ?? [self::LEVEL_VIEW => 'Смотреть']);
                ksort($levels);
                $list[(string)$code] = [
                    'name'     => (string)($config['name'] ?? $code),
                    'levels'   => $levels,
                    'hint'     => (string)($config['hint'] ?? ''),
                    'priority' => (int)($config['priority'] ?? 1000),
                    'max'      => (int)max(array_keys($levels) ?: [self::LEVEL_VIEW]),
                    //Core areas are open in a type without settings; an area of a package only where a type allows it.
                    'openByDefault' => (bool)($config['openByDefault'] ?? isset(static::coreAreas()[$code])),
                ];
            }
            uasort($list, function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            $this->_areaList = $list;
        }

        return $this->_areaList;
    }

    public function hasArea(string $area): bool
    {
        return isset($this->getAreaList()[$area]);
    }

    public function areaName(string $area): string
    {
        return $this->getAreaList()[$area]['name'] ?? $area;
    }

    public function levelName(string $area, int $level): string
    {
        if ($level <= self::LEVEL_NONE) {
            return 'Нет';
        }
        $levels = $this->getAreaList()[$area]['levels'] ?? [];
        $name = 'Нет';
        foreach ($levels as $value => $label) {
            if ($value <= $level) {
                $name = $label;
            }
        }
        return $name;
    }

    /**
     * Normalizes posted levels: unknown areas are dropped, a level becomes the
     * highest declared level of its area not above it.
     *
     * @param array $levels area => level
     * @return array area => level, only levels above none
     */
    public function normalizeLevels(array $levels): array
    {
        $result = [];
        foreach ($this->getAreaList() as $area => $config) {
            $level = (int)($levels[$area] ?? 0);
            $normalized = self::LEVEL_NONE;
            foreach (array_keys($config['levels']) as $value) {
                if ($value <= $level) {
                    $normalized = (int)$value;
                }
            }
            if ($normalized > self::LEVEL_NONE) {
                $result[$area] = $normalized;
            }
        }
        return $result;
    }

    /**
     * Deals the person may see. Without an area — any grant on the deal; with an
     * area — the area is open in the deal and granted to the person at least at
     * $level.
     *
     * @param CmsUser|int $user
     */
    public function dealsQuery($user, ?string $area = null, int $level = self::LEVEL_VIEW): CmsDealQuery
    {
        $query = CmsDeal::find();
        $query->andWhere($this->dealCondition($user, $area, $level, CmsDeal::tableName()));
        return $query;
    }

    /**
     * The SQL condition of dealsQuery() for a deal table alias: use it in
     * subqueries ("tasks of deals where the person may see tasks").
     *
     * @param CmsUser|int $user
     * @param string      $dealAlias the alias or table name of cms_deal in the outer query
     * @return array|Expression
     */
    public function dealCondition($user, ?string $area = null, int $level = self::LEVEL_VIEW, string $dealAlias = 'cms_deal')
    {
        $userId = $this->userId($user);
        if (!$userId) {
            return new Expression('1=0');
        }

        if ($area !== null) {
            if (!$this->hasArea($area) || $level > $this->getAreaList()[$area]['max'] || $level <= self::LEVEL_NONE) {
                return new Expression('1=0');
            }
        }

        $grantArea = (new Query())
            ->select(new Expression('1'))
            ->from(['caa' => '{{%cms_client_access_area}}'])
            ->where('caa.cms_client_access_id = ca.id')
            ->andWhere(['caa.area' => $area])
            ->andWhere(['>=', 'caa.level', $level]);

        $grant = (new Query())
            ->select(new Expression('1'))
            ->from(['ca' => '{{%cms_client_access}}'])
            ->where(['ca.cms_user_id' => $userId])
            ->andWhere([
                'or',
                "ca.cms_deal_id = {$dealAlias}.id",
                ['and', ['ca.cms_deal_id' => null], "ca.cms_company_id = {$dealAlias}.cms_company_id"],
            ]);
        if ($area !== null) {
            $grant->andWhere(['or', ['ca.is_full' => 1], ['exists', $grantArea]]);
        }

        $granted = [
            'or',
            ['and', ["{$dealAlias}.cms_company_id" => null], ["{$dealAlias}.cms_user_id" => $userId]],
            ['exists', $grant],
        ];

        if ($area === null) {
            return $granted;
        }

        return ['and', $granted, $this->openCondition($area, $level, $dealAlias)];
    }

    /**
     * Deals where the person has **full** access: the owner of a deal without a
     * company, a full grant on the deal or on all deals of its company. This is
     * what "deals of the client" meant before access areas (is_root, the contact
     * of a deal), so screens that do not ask for an area keep exactly that
     * boundary and never widen to limited roles.
     *
     * @param CmsUser|int $user
     */
    public function fullCondition($user, string $dealAlias = 'cms_deal')
    {
        $userId = $this->userId($user);
        if (!$userId) {
            return new Expression('1=0');
        }
        $grant = (new Query())
            ->select(new Expression('1'))
            ->from(['ca' => '{{%cms_client_access}}'])
            ->where(['ca.cms_user_id' => $userId, 'ca.is_full' => 1])
            ->andWhere([
                'or',
                "ca.cms_deal_id = {$dealAlias}.id",
                ['and', ['ca.cms_deal_id' => null], "ca.cms_company_id = {$dealAlias}.cms_company_id"],
            ]);

        return [
            'or',
            ['and', ["{$dealAlias}.cms_company_id" => null], ["{$dealAlias}.cms_user_id" => $userId]],
            ['exists', $grant],
        ];
    }

    /**
     * The area is open in the deal at least at $level: the type allows it (a
     * type without rows allows the core areas; an area of a package needs an
     * explicit type row) and the deal does not narrow it.
     */
    public function openCondition(string $area, int $level, string $dealAlias = 'cms_deal'): array
    {
        $typeAny = (new Query())
            ->select(new Expression('1'))
            ->from(['dta' => '{{%cms_deal_type_area}}'])
            ->where("dta.cms_deal_type_id = {$dealAlias}.cms_deal_type_id");
        $typeArea = (clone $typeAny)
            ->andWhere(['dta.area' => $area])
            ->andWhere(['>=', 'dta.level', $level]);
        $dealNarrowed = (new Query())
            ->select(new Expression('1'))
            ->from(['da' => '{{%cms_deal_area}}'])
            ->where("da.cms_deal_id = {$dealAlias}.id")
            ->andWhere(['da.area' => $area])
            ->andWhere(['<', 'da.level', $level]);

        //A new possibility of a package must not open to clients by itself: only an explicit type row opens it.
        $openByDefault = !empty($this->getAreaList()[$area]['openByDefault']);

        return [
            'and',
            $openByDefault ? ['or', ['not exists', $typeAny], ['exists', $typeArea]] : ['exists', $typeArea],
            ['not exists', $dealNarrowed],
        ];
    }

    /**
     * @param CmsUser|int  $user
     * @param CmsDeal|int  $deal
     */
    public function can($user, $deal, string $area, int $level = self::LEVEL_VIEW): bool
    {
        $dealId = $deal instanceof CmsDeal ? (int)$deal->id : (int)$deal;
        if (!$dealId) {
            return false;
        }
        return $this->dealsQuery($user, $area, $level)
            ->andWhere([CmsDeal::tableName().'.id' => $dealId])
            ->exists();
    }

    /**
     * The effective level of the person in the area of the deal.
     */
    public function level($user, $deal, string $area): int
    {
        if (!$this->hasArea($area)) {
            return self::LEVEL_NONE;
        }
        $levels = array_reverse(array_keys($this->getAreaList()[$area]['levels']));
        foreach ($levels as $level) {
            if ($this->can($user, $deal, $area, (int)$level)) {
                return (int)$level;
            }
        }
        return self::LEVEL_NONE;
    }

    /**
     * Effective levels of the person in every area of the deal.
     *
     * @return array area => level (none included)
     */
    public function levels($user, $deal): array
    {
        $result = [];
        foreach (array_keys($this->getAreaList()) as $area) {
            $result[$area] = $this->level($user, $deal, $area);
        }
        return $result;
    }

    /**
     * How far the area is open in the deal (independently of people).
     */
    public function openLevel(CmsDeal $deal, string $area): int
    {
        if (!$this->hasArea($area)) {
            return self::LEVEL_NONE;
        }
        $levels = array_reverse(array_keys($this->getAreaList()[$area]['levels']));
        foreach ($levels as $level) {
            $open = CmsDeal::find()
                ->andWhere([CmsDeal::tableName().'.id' => $deal->id])
                ->andWhere($this->openCondition($area, (int)$level, CmsDeal::tableName()))
                ->exists();
            if ($open) {
                return (int)$level;
            }
        }
        return self::LEVEL_NONE;
    }

    /**
     * The highest declared level of the area not above $level.
     */
    public function capLevel(string $area, int $level): int
    {
        $result = self::LEVEL_NONE;
        foreach (array_keys($this->getAreaList()[$area]['levels'] ?? []) as $value) {
            if ($value <= $level) {
                $result = (int)$value;
            }
        }
        return $result;
    }

    /**
     * What the deal type allows to open: area => level, or null when the type
     * has no settings (core areas open, package areas closed).
     */
    public function typeOpenLevels(?int $typeId): ?array
    {
        $rows = $typeId ? (new Query())->select(['area', 'level'])->from('{{%cms_deal_type_area}}')->where(['cms_deal_type_id' => $typeId])->all() : [];
        if (!$rows) {
            return null;
        }
        $result = array_fill_keys(array_keys($this->getAreaList()), self::LEVEL_NONE);
        foreach ($rows as $row) {
            if (isset($result[$row['area']])) {
                $result[$row['area']] = $this->capLevel((string)$row['area'], (int)$row['level']);
            }
        }
        return $result;
    }

    /**
     * How far each area is open in the deal and why, counted the same way as
     * openCondition() but from two reads, for cards and forms.
     *
     * @return array area => ['type' => level allowed by the type, 'typeSet' => the type has settings,
     *               'deal' => level the deal narrowed to or null, 'open' => effective level,
     *               'limit' => null|'type'|'deal' what keeps it below the maximum]
     */
    public function openInfo(CmsDeal $deal): array
    {
        $typeLevels = $this->typeOpenLevels($deal->cms_deal_type_id ? (int)$deal->cms_deal_type_id : null);
        $dealRows = (new Query())->select(['level', 'area'])->from('{{%cms_deal_area}}')->where(['cms_deal_id' => (int)$deal->id])->indexBy('area')->column();

        $result = [];
        foreach ($this->getAreaList() as $area => $config) {
            $type = $typeLevels === null ? ($config['openByDefault'] ? $config['max'] : self::LEVEL_NONE) : (int)$typeLevels[$area];
            $narrow = array_key_exists($area, $dealRows) ? $this->capLevel($area, (int)$dealRows[$area]) : null;
            $open = $narrow === null ? $type : min($type, $narrow);
            $limit = null;
            if ($open < $config['max']) {
                $limit = $narrow !== null && $narrow < $type ? 'deal' : 'type';
            }
            $result[$area] = ['type' => $type, 'typeSet' => $typeLevels !== null, 'deal' => $narrow, 'open' => $open, 'limit' => $limit];
        }
        return $result;
    }

    /**
     * Saves what the deal type allows to open. Null removes the settings (core
     * areas open, package areas closed); an array stores every area, closed
     * ones as 0, so a type can close everything.
     */
    public function saveTypeOpenLevels(int $typeId, ?array $levels): void
    {
        $db = CmsDeal::getDb();
        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete('{{%cms_deal_type_area}}', ['cms_deal_type_id' => $typeId])->execute();
            if ($levels !== null) {
                $rows = [];
                foreach (array_keys($this->getAreaList()) as $area) {
                    $rows[] = [$typeId, $area, $this->capLevel($area, (int)($levels[$area] ?? 0))];
                }
                $db->createCommand()->batchInsert('{{%cms_deal_type_area}}', ['cms_deal_type_id', 'area', 'level'], $rows)->execute();
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Saves what the deal itself opens: only levels below what the type allows
     * are stored (a deal narrows, never widens). Writes the change into the
     * deal feed.
     *
     * @param array $levels area => wanted level
     */
    public function saveDealOpenLevels(CmsDeal $deal, array $levels): void
    {
        $before = $this->openInfo($deal);
        $db = CmsDeal::getDb();
        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete('{{%cms_deal_area}}', ['cms_deal_id' => (int)$deal->id])->execute();
            $rows = [];
            foreach ($before as $area => $info) {
                $wanted = $this->capLevel($area, (int)($levels[$area] ?? $info['type']));
                if ($wanted < $info['type']) {
                    $rows[] = [(int)$deal->id, $area, $wanted];
                }
            }
            if ($rows) {
                $db->createCommand()->batchInsert('{{%cms_deal_area}}', ['cms_deal_id', 'area', 'level'], $rows)->execute();
            }

            $changes = [];
            foreach ($this->openInfo($deal) as $area => $info) {
                if ($info['open'] !== $before[$area]['open']) {
                    $changes[] = $this->areaName($area).': '.mb_strtolower($info['open'] ? $this->levelName($area, $info['open']) : 'закрыто');
                }
            }
            if ($changes) {
                $this->writeDealLog($deal, 'Изменено, что открыто клиенту: '.implode('; ', $changes).'.');
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Effective rights of every client person in the deal with their origin:
     * the same answer as levels(), counted from dealGrants() and openInfo() in
     * PHP so a card does not run a query per area and level.
     *
     * @return array user id => [
     *   'user' => CmsUser|null, 'owner' => bool, 'grants' => CmsClientAccess[],
     *   'areas' => area => ['level' => effective, 'granted' => level before openness,
     *              'source' => null|'owner'|'company'|'deal', 'grant' => CmsClientAccess|null,
     *              'open' => open level, 'limit' => null|'type'|'deal' (only when it cut the grant)]
     * ]
     */
    public function explain(CmsDeal $deal, ?array $grants = null): array
    {
        $grants = $grants ?? $this->dealGrants($deal);
        $open = $this->openInfo($deal);

        $people = [];
        if (!$deal->cms_company_id && $deal->cms_user_id) {
            $people[(int)$deal->cms_user_id] = ['user' => $deal->user, 'owner' => true, 'grants' => []];
        }
        foreach ($grants as $grant) {
            $userId = (int)$grant->cms_user_id;
            if (!isset($people[$userId])) {
                $people[$userId] = ['user' => $grant->cmsUser, 'owner' => false, 'grants' => []];
            }
            $people[$userId]['grants'][] = $grant;
        }

        foreach ($people as $userId => $person) {
            //Full access (owner, company-wide full grant) covers the other grants: it is named as the origin.
            $covering = $person['owner'];
            foreach ($person['grants'] as $grant) {
                if (!$grant->cms_deal_id && $grant->is_full) {
                    $covering = true;
                }
            }
            $areas = [];
            foreach ($this->getAreaList() as $area => $config) {
                $granted = self::LEVEL_NONE;
                $source = null;
                $from = null;
                if ($person['owner']) {
                    $granted = $config['max'];
                    $source = 'owner';
                }
                foreach ($person['grants'] as $grant) {
                    $level = $grant->is_full ? $config['max'] : $this->capLevel($area, (int)($grant->getLevels()[$area] ?? 0));
                    //A tie: the covering company-wide full grant wins; without full access a deal grant wins
                    //over a company-wide one, being what was set for this service.
                    $tieWins = $covering
                        ? (!$grant->cms_deal_id && $grant->is_full && $source !== 'owner')
                        : ($grant->cms_deal_id && $source === 'company');
                    if ($level > $granted || ($level === $granted && $level > 0 && $tieWins)) {
                        $granted = $level;
                        $source = $grant->cms_deal_id ? 'deal' : 'company';
                        $from = $grant;
                    }
                }
                $level = min($granted, $open[$area]['open']);
                $areas[$area] = [
                    'level'   => $level,
                    'granted' => $granted,
                    'source'  => $granted ? $source : null,
                    'grant'   => $from,
                    'open'    => $open[$area]['open'],
                    'limit'   => $granted > $level ? $open[$area]['limit'] : null,
                ];
            }
            $people[$userId]['areas'] = $areas;
        }

        return $people;
    }

    /**
     * People who may use the area of the deal at least at $level: recipients of
     * notifications (У4), people of a working chat.
     *
     * @return \yii\db\ActiveQuery of CmsUser
     */
    public function usersQuery(CmsDeal $deal, string $area, int $level = self::LEVEL_VIEW)
    {
        $userTable = CmsUser::tableName();
        $query = CmsUser::find();

        if ($this->openLevel($deal, $area) < $level || $level <= self::LEVEL_NONE || $level > ($this->getAreaList()[$area]['max'] ?? 0)) {
            return $query->andWhere(new Expression('1=0'));
        }

        $grantArea = (new Query())
            ->select(new Expression('1'))
            ->from(['caa' => '{{%cms_client_access_area}}'])
            ->where('caa.cms_client_access_id = ca.id')
            ->andWhere(['caa.area' => $area])
            ->andWhere(['>=', 'caa.level', $level]);

        $grantUserIds = (new Query())
            ->select('ca.cms_user_id')
            ->from(['ca' => '{{%cms_client_access}}'])
            ->where([
                'or',
                ['ca.cms_deal_id' => (int)$deal->id],
                $deal->cms_company_id ? ['and', ['ca.cms_deal_id' => null], ['ca.cms_company_id' => (int)$deal->cms_company_id]] : new Expression('1=0'),
            ])
            ->andWhere(['or', ['ca.is_full' => 1], ['exists', $grantArea]]);

        $condition = ['or', ["{$userTable}.id" => $grantUserIds]];
        if (!$deal->cms_company_id && $deal->cms_user_id) {
            $condition[] = ["{$userTable}.id" => (int)$deal->cms_user_id];
        }

        return $query->andWhere($condition);
    }

    /**
     * Grants that act on the deal: on the deal itself and company-wide ones.
     *
     * @return \skeeks\cms\models\CmsClientAccess[]
     */
    public function dealGrants(CmsDeal $deal): array
    {
        $query = \skeeks\cms\models\CmsClientAccess::find()
            ->with(['cmsUser', 'cmsDealRole', 'areaRows'])
            ->andWhere([
                'or',
                ['cms_deal_id' => (int)$deal->id],
                $deal->cms_company_id ? ['and', ['cms_deal_id' => null], ['cms_company_id' => (int)$deal->cms_company_id]] : new Expression('1=0'),
            ])
            ->orderBy(['cms_deal_id' => SORT_ASC, 'id' => SORT_ASC]);

        return $query->all();
    }

    /**
     * @return DealResourceProviderInterface[] type => provider
     */
    public function getProviders(): array
    {
        $result = [];
        foreach (array_keys((array)$this->resourceProviders) as $type) {
            if ($provider = $this->getProvider((string)$type)) {
                $result[(string)$type] = $provider;
            }
        }
        return $result;
    }

    public function getProvider(string $type): ?DealResourceProviderInterface
    {
        if (!isset($this->resourceProviders[$type])) {
            return null;
        }
        if (!isset($this->_providers[$type])) {
            $provider = \Yii::createObject($this->resourceProviders[$type]);
            if (!$provider instanceof DealResourceProviderInterface) {
                throw new InvalidConfigException("Deal resource provider «{$type}» must implement ".DealResourceProviderInterface::class);
            }
            $this->_providers[$type] = $provider;
        }
        return $this->_providers[$type];
    }

    /**
     * Resources of the deal from every registered provider.
     *
     * @return DealResource[]
     */
    public function resources(CmsDeal $deal): array
    {
        $result = [];
        foreach ($this->getProviders() as $type => $provider) {
            foreach ($provider->findByDeal($deal) as $resource) {
                $resource->type = $type;
                $result[] = $resource;
            }
        }
        return $result;
    }

    public function resource(string $type, int $id): ?DealResource
    {
        $provider = $this->getProvider($type);
        if (!$provider) {
            return null;
        }
        $resource = $provider->findOne($id);
        if ($resource) {
            $resource->type = $type;
        }
        return $resource;
    }

    /**
     * Moves a resource into another deal and writes the move into the feeds of
     * both deals.
     *
     * @throws \yii\base\UserException
     */
    public function moveResource(string $type, int $id, CmsDeal $toDeal): DealResource
    {
        $provider = $this->getProvider($type);
        $resource = $provider ? $provider->findOne($id) : null;
        if (!$resource) {
            throw new \yii\base\UserException('Ресурс не найден.');
        }
        $resource->type = $type;
        if ((int)$resource->dealId === (int)$toDeal->id) {
            return $resource;
        }

        $fromDeal = $resource->dealId ? CmsDeal::findOne((int)$resource->dealId) : null;
        $transaction = CmsDeal::getDb()->beginTransaction();
        try {
            $provider->move($id, $toDeal);
            $label = $provider->label().' «'.$resource->title.'»';

            //Rule: tasks and composition lines of the resource stay in the previous deal as its history
            //(their link to the resource is kept); new links are made only within the resource's deal.
            $left = '';
            if ($fromDeal) {
                $tasks = \skeeks\cms\models\queries\CmsTaskQuery::hasDealColumn() ? (int)\skeeks\cms\models\CmsTask::find()
                    ->andWhere(['cms_deal_id' => (int)$fromDeal->id, 'deal_resource_type' => $type, 'deal_resource_id' => $id])->count() : 0;
                $lines = (int)\skeeks\cms\models\CmsDealItem::find()
                    ->andWhere(['cms_deal_id' => (int)$fromDeal->id, 'resource_type' => $type, 'resource_id' => $id])->count();
                if ($tasks || $lines) {
                    $left = " В прежней услуге остались как история: задач {$tasks}, строк состава {$lines}.";
                }
                $this->writeDealLog($fromDeal, "{$label} перенесён в услугу «{$toDeal->asText}».{$left}");
            }
            $this->writeDealLog($toDeal, $fromDeal
                ? "{$label} перенесён из услуги «{$fromDeal->asText}».{$left}"
                : "{$label} добавлен в услугу.");
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $resource->dealId = (int)$toDeal->id;
        return $resource;
    }

    /**
     * Link to a route of the client cabinet (UPA) from any application context,
     * e.g. for the "в кабинете клиента" link in the deal card.
     */
    public function clientUrl(?array $route): ?string
    {
        if (!$route) {
            return null;
        }
        $prefix = '~upa';
        if (\Yii::$app->has('upa')) {
            $prefix = (string)\yii\helpers\ArrayHelper::getValue(\Yii::$app->upa->urlRule, 'urlPrefix', $prefix);
        }
        $path = trim((string)array_shift($route), '/');
        $baseUrl = \Yii::$app->has('request') && \Yii::$app->request instanceof \yii\web\Request ? (string)\Yii::$app->request->baseUrl : '';
        return rtrim($baseUrl, '/').'/'.trim($prefix, '/').'/'.$path.($route ? '?'.http_build_query($route) : '');
    }

    /**
     * A plain comment entry in the deal feed.
     */
    public function writeDealLog(CmsDeal $deal, string $comment): void
    {
        $log = new \skeeks\cms\models\CmsLog();
        $log->log_type = \skeeks\cms\models\CmsLog::LOG_TYPE_COMMENT;
        $log->model_code = $deal->skeeksModelCode;
        $log->model_id = $deal->id;
        $log->model_as_text = $deal->asText;
        $log->comment = $comment;
        $log->is_pinned = 0;
        if (!$log->save()) {
            throw new \RuntimeException(implode('; ', $log->getFirstErrors()));
        }
    }

    /**
     * @param CmsUser|int|null $user
     */
    protected function userId($user): int
    {
        if ($user instanceof \yii\db\BaseActiveRecord) {
            return (int)$user->getPrimaryKey();
        }
        return (int)$user;
    }
}
