<?php

namespace skeeks\cms\crm;

use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadEmail;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\CmsUserPhone;
use skeeks\cms\models\User;
use yii\base\Component;
use yii\db\Expression;
use yii\db\Query;

/**
 * The one duplicate check of the CRM: who already has this email, phone,
 * INN, domain or name — companies, people (not employees) and open deals
 * (CmsLead) — across the whole base of the site, including records the
 * employee cannot see.
 *
 * It only finds candidates with every piece of evidence; it never links or
 * merges anything. Email, phone and INN are strong evidence; a name and a
 * domain are weak (hints only). What an employee may see of a hidden
 * candidate is decided by CrmAccess::presentCandidate(): "такой клиент уже
 * есть, ответственный …", without contacts, letters or amounts.
 *
 * A request duplicate (the same need) is a different question: repeatsOf()
 * lists open deals of the found clients; a person decides whether the new
 * request continues one of them. An event duplicate (the same form, letter,
 * webhook) is found by source_type + source_ref in CmsLeadService.
 *
 * Usage: Yii::$app->crmMatcher->find([...], $viewer).
 */
class CrmMatcher extends Component
{
    public const TYPE_COMPANY = 'company';
    public const TYPE_CLIENT = 'client';
    public const TYPE_LEAD = 'lead';

    public const BY_EMAIL = 'email';
    public const BY_PHONE = 'phone';
    public const BY_INN = 'inn';
    public const BY_DOMAIN = 'domain';
    public const BY_NAME = 'name';

    /** Evidence that identifies a record; the rest are hints. */
    public const STRONG = [self::BY_EMAIL, self::BY_PHONE, self::BY_INN];

    /**
     * Mail services shared by many people: their domain says nothing about a
     * company. Configure the component to add local ones.
     */
    public array $publicMailDomains = [
        'gmail.com', 'googlemail.com', 'yandex.ru', 'ya.ru', 'yandex.com', 'yandex.by', 'yandex.kz', 'yandex.ua',
        'narod.ru', 'mail.ru', 'bk.ru', 'list.ru', 'inbox.ru', 'internet.ru', 'mail.ua', 'rambler.ru', 'lenta.ru',
        'autorambler.ru', 'myrambler.ru', 'ro.ru', 'icloud.com', 'me.com', 'mac.com', 'outlook.com', 'hotmail.com',
        'live.com', 'live.ru', 'msn.com', 'yahoo.com', 'aol.com', 'proton.me', 'protonmail.com', 'gmx.com', 'gmx.de',
        'qip.ru', 'pochta.ru', 'e1.ru', 'ngs.ru', 'ukr.net', 'tut.by', 'vk.com', 'zoho.com', 'yandex.com.tr',
    ];

    /** Legal forms removed before names are compared: «ООО «Ромашка»» = «Ромашка». */
    public array $legalForms = ['ооо', 'оао', 'зао', 'пао', 'ао', 'ип', 'нко', 'ано', 'фгуп', 'гуп', 'муп', 'llc', 'ltd', 'inc'];

    /**
     * Deals that count as candidates: open ones by default (a closed attempt
     * is history). null — every deal (the import marks of CrmDuplicates).
     */
    public ?array $leadStatuses = [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK];

    /** Open leads limit per client in repeatsOf(). */
    public int $repeatsLimit = 10;

    /** The configured component, or a default one (consoles, tests, old configs). */
    public static function instance(): self
    {
        return \Yii::$app->has('crmMatcher') ? \Yii::$app->get('crmMatcher') : new self();
    }

    /** @var array|null phone key => list of [type, id]; for findMany() only */
    private ?array $_phoneIndex = null;
    private ?int $_phoneIndexSite = null;

    /**
     * Candidates for one set of contacts, strongest first, presented for the
     * viewer (hidden ones masked). Without a viewer the raw candidates are
     * returned: system code only, never to a person.
     *
     * @param array $input phones[], emails[], inns[], domains[], name, siteId,
     *                     excludeLeadIds[], types[] (company, client, lead)
     * @return array[] see presentCandidate() / raw: type, id, reasons, strong
     */
    public function find(array $input, ?User $viewer = null): array
    {
        $candidates = $this->rawFind($input);
        if ($viewer === null) {
            return array_values($candidates);
        }

        return $this->present($candidates, $viewer, (int)($input['siteId'] ?? $this->siteId()));
    }

    /**
     * Raw candidates keyed "type:id": type, id, reasons (by, value, strong),
     * strong (has strong evidence), score.
     */
    public function rawFind(array $input): array
    {
        $siteId = (int)($input['siteId'] ?? $this->siteId());
        $types = (array)($input['types'] ?? [self::TYPE_COMPANY, self::TYPE_CLIENT, self::TYPE_LEAD]);
        $exclude = array_map('intval', (array)($input['excludeLeadIds'] ?? []));

        $emails = $this->normalizeEmails((array)($input['emails'] ?? []));
        $phones = $this->normalizePhones((array)($input['phones'] ?? []));
        $inns = array_values(array_filter(array_unique(array_map([self::class, 'normalizeInn'], (array)($input['inns'] ?? []))), 'strlen'));
        $domains = $this->companyDomains(array_merge((array)($input['domains'] ?? []), $emails));
        $name = self::normalizeName((string)($input['name'] ?? ''), $this->legalForms);

        $found = [];
        $add = function (string $type, int $id, string $by, string $value) use (&$found, $types, $exclude) {
            if (!in_array($type, $types, true) || $id <= 0 || ($type === self::TYPE_LEAD && in_array($id, $exclude, true))) {
                return;
            }
            $key = $type.':'.$id;
            $found[$key] = $found[$key] ?? ['type' => $type, 'id' => $id, 'reasons' => []];
            $reasonKey = $by.':'.$value;
            $found[$key]['reasons'][$reasonKey] = ['by' => $by, 'value' => $value, 'strong' => in_array($by, self::STRONG, true)];
        };

        foreach ($this->byEmails($emails, $siteId) as [$type, $id, $value]) {
            $add($type, $id, self::BY_EMAIL, $value);
        }
        foreach ($this->byPhones($phones, $siteId) as [$type, $id, $value]) {
            $add($type, $id, self::BY_PHONE, $value);
        }
        foreach ($this->byInns($inns, $siteId) as [$type, $id, $value]) {
            $add($type, $id, self::BY_INN, $value);
        }
        foreach ($this->byDomains($domains) as [$type, $id, $value]) {
            $add($type, $id, self::BY_DOMAIN, $value);
        }
        if ($name !== '') {
            foreach ($this->byNames([$name => (string)$input['name']], $siteId) as [$type, $id, $value]) {
                $add($type, $id, self::BY_NAME, $value);
            }
        }

        return $this->finish($found);
    }

    /**
     * Many candidates at once (imports): key => the same as rawFind() for
     * that key. Phones of the base are read once per instance and site.
     *
     * @param array[] $inputs key => input of rawFind()
     * @return array[] key => raw candidates (only keys with something found)
     */
    public function findMany(array $inputs, int $siteId): array
    {
        $result = [];
        $this->loadPhoneIndex($siteId);
        $allEmails = [];
        $allInns = [];
        $allNames = [];
        foreach ($inputs as $input) {
            foreach ($this->normalizeEmails((array)($input['emails'] ?? [])) as $email) {
                $allEmails[$email] = true;
            }
            foreach ((array)($input['inns'] ?? []) as $inn) {
                if (($inn = self::normalizeInn((string)$inn)) !== '') {
                    $allInns[$inn] = true;
                }
            }
            if (($name = self::normalizeName((string)($input['name'] ?? ''), $this->legalForms)) !== '') {
                $allNames[$name] = (string)$input['name'];
            }
        }
        $emailHits = $this->group($this->byEmails(array_keys($allEmails), $siteId));
        $innHits = $this->group($this->byInns(array_keys($allInns), $siteId));
        $nameHits = $this->group($allNames ? $this->byNames($allNames, $siteId) : [], true);

        foreach ($inputs as $key => $input) {
            $types = (array)($input['types'] ?? [self::TYPE_COMPANY, self::TYPE_CLIENT, self::TYPE_LEAD]);
            $found = [];
            $add = function (array $hits, string $by) use (&$found, $types) {
                foreach ($hits as [$type, $id, $value]) {
                    if (!in_array($type, $types, true)) {
                        continue;
                    }
                    $k = $type.':'.$id;
                    $found[$k] = $found[$k] ?? ['type' => $type, 'id' => $id, 'reasons' => []];
                    $found[$k]['reasons'][$by.':'.$value] = ['by' => $by, 'value' => $value, 'strong' => in_array($by, self::STRONG, true)];
                }
            };
            foreach ($this->normalizeEmails((array)($input['emails'] ?? [])) as $email) {
                $add($emailHits[$email] ?? [], self::BY_EMAIL);
            }
            foreach ($this->normalizePhones((array)($input['phones'] ?? [])) as $phoneKey => $phone) {
                $add(array_map(static fn($hit) => [$hit[0], $hit[1], $phone], $this->_phoneIndex[$phoneKey] ?? []), self::BY_PHONE);
            }
            foreach ((array)($input['inns'] ?? []) as $inn) {
                if (($inn = self::normalizeInn((string)$inn)) !== '') {
                    $add($innHits[$inn] ?? [], self::BY_INN);
                }
            }
            if (($name = self::normalizeName((string)($input['name'] ?? ''), $this->legalForms)) !== '') {
                $add($nameHits[$name] ?? [], self::BY_NAME);
            }
            if ($found) {
                $result[$key] = $this->finish($found);
            }
        }

        return $result;
    }

    /** Remembers a lead made during a batch: later rows find it by phone. */
    public function rememberLeadPhones(int $leadId, array $phones): void
    {
        if ($this->_phoneIndex === null) {
            return;
        }
        foreach ($this->normalizePhones($phones) as $key => $phone) {
            $this->_phoneIndex[$key][] = [self::TYPE_LEAD, $leadId];
        }
    }

    /**
     * Open deals (new, in work) of the found companies and people and those
     * sharing a strong contact: the candidates for "the same request". Not
     * merged: a person chooses.
     *
     * @param array $candidates raw candidates of rawFind()
     * @return int[] lead ids, newest first
     */
    public function repeatsOf(array $candidates, int $siteId, array $excludeLeadIds = []): array
    {
        $companyIds = [];
        $clientIds = [];
        $leadIds = [];
        foreach ($candidates as $candidate) {
            if (empty($candidate['strong'])) {
                continue;
            }
            if ($candidate['type'] === self::TYPE_COMPANY) {
                $companyIds[] = (int)$candidate['id'];
            } elseif ($candidate['type'] === self::TYPE_CLIENT) {
                $clientIds[] = (int)$candidate['id'];
            } else {
                $leadIds[] = (int)$candidate['id'];
            }
        }
        if (!$companyIds && !$clientIds && !$leadIds) {
            return [];
        }
        $table = CmsLead::tableName();
        $condition = ['or'];
        if ($companyIds) {
            $condition[] = [$table.'.cms_company_id' => $companyIds];
        }
        if ($clientIds) {
            $condition[] = [$table.'.cms_user_id' => $clientIds];
        }
        if ($leadIds) {
            $condition[] = [$table.'.id' => $leadIds];
        }

        return array_map('intval', CmsLead::find()
            ->select($table.'.id')
            ->andWhere([$table.'.cms_site_id' => $siteId])
            ->andWhere([$table.'.status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])
            ->andWhere($condition)
            ->andFilterWhere(['not in', $table.'.id', $excludeLeadIds])
            ->orderBy([$table.'.id' => SORT_DESC])
            ->limit($this->repeatsLimit * max(1, count($companyIds) + count($clientIds)))
            ->column());
    }

    /**
     * Contacts of a lead as matcher input: its phones, emails, INNs of its
     * requisites, its company name is not known — the lead name is a title.
     */
    public function inputOfLead(CmsLead $lead): array
    {
        return [
            'siteId' => (int)$lead->cms_site_id,
            'phones' => array_map(static fn($phone) => (string)$phone->value, $lead->phones),
            'emails' => array_map(static fn($email) => (string)$email->value, $lead->emails),
            'inns' => array_map(static fn($contractor) => (string)$contractor->inn, $lead->contractors),
            'excludeLeadIds' => [(int)$lead->id],
        ];
    }

    /**
     * The search field of a form (П2а): a typed or pasted line is parsed
     * (CrmContactParser), matched across the base and completed with the
     * viewer's own records by a part of the name, as a usual search does.
     *
     * @param string $for TYPE_COMPANY or TYPE_CLIENT
     * @return array candidates (presented for the viewer, at most $limit), parsed
     */
    public function searchLine(string $line, string $for, User $viewer, int $siteId, int $limit = 10): array
    {
        $parsed = CrmContactParser::parse($line);
        if ($for === self::TYPE_CLIENT && $parsed['name'] !== '' && $parsed['person'] === '') {
            // In the contact field a text is the person's name.
            $parsed['person'] = $parsed['name'];
        }
        $raw = $this->rawFind(CrmContactParser::matcherInput($parsed, [
            'siteId' => $siteId,
            'types' => [$for],
            // People's names repeat too often: a contact is matched by its contacts only.
            'name' => $for === self::TYPE_COMPANY ? $parsed['name'] : '',
        ]));
        $text = $for === self::TYPE_COMPANY ? $parsed['name'] : $parsed['person'];
        if (mb_strlen($text) >= 2) {
            $own = $for === self::TYPE_COMPANY
                ? CmsCompany::find()->forManager($viewer)->search($text)->select(CmsCompany::tableName().'.id')->distinct()->limit(7)->column()
                : CmsUser::find()->forManager($viewer)->andWhere([CmsUser::tableName().'.cms_site_id' => $siteId, CmsUser::tableName().'.is_worker' => 0])
                    ->search($text)->select(CmsUser::tableName().'.id')->distinct()->limit(7)->column();
            foreach ($own as $id) {
                $key = $for.':'.(int)$id;
                $raw[$key] = $raw[$key] ?? ['type' => $for, 'id' => (int)$id, 'reasons' => [['by' => self::BY_NAME, 'value' => $text, 'strong' => false]], 'strong' => false, 'score' => 0];
            }
        }

        return [
            'candidates' => array_slice($this->present($raw, $viewer, $siteId), 0, $limit),
            'parsed' => $parsed,
        ];
    }

    /** Candidates as an employee may see them: CrmAccess::presentCandidate() each. */
    public function present(array $candidates, User $viewer, ?int $siteId = null): array
    {
        $byType = [];
        foreach ($candidates as $candidate) {
            $byType[$candidate['type']][] = (int)$candidate['id'];
        }
        $visible = [];
        foreach ($byType as $type => $ids) {
            $visible[$type] = array_flip($this->visibleIds($type, $ids, $viewer));
        }
        $access = CrmAccess::instance();
        $result = [];
        foreach ($candidates as $candidate) {
            $result[] = $access->presentCandidate($candidate, isset($visible[$candidate['type']][(int)$candidate['id']]), $viewer);
        }

        return $result;
    }

    /** @return int[] ids of the type the viewer may open */
    public function visibleIds(string $type, array $ids, User $viewer): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        switch ($type) {
            case self::TYPE_COMPANY:
                return array_map('intval', CmsCompany::find()->forManager($viewer)
                    ->andWhere([CmsCompany::tableName().'.id' => $ids])->select(CmsCompany::tableName().'.id')->distinct()->column());
            case self::TYPE_CLIENT:
                return array_map('intval', CmsUser::find()->forManager($viewer)
                    ->andWhere([CmsUser::tableName().'.id' => $ids])->select(CmsUser::tableName().'.id')->distinct()->column());
            case self::TYPE_LEAD:
                return array_map('intval', CmsLead::find()->forManager($viewer)
                    ->andWhere([CmsLead::tableName().'.id' => $ids])->select(CmsLead::tableName().'.id')->distinct()->column());
        }

        return [];
    }

    /** "ООО «Ромашка»  " → "ромашка"; legal forms only when they are separate words. */
    public static function normalizeName(string $name, array $legalForms = []): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        $name = str_replace(['«', '»', '"', '“', '”', '„', "'", '.', ','], ' ', $name);
        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_filter($words, static fn($word) => !in_array($word, $legalForms, true)));

        return implode(' ', $words);
    }

    public static function normalizeInn(string $inn): string
    {
        $digits = PhoneHelper::digits($inn);

        return in_array(strlen($digits), [10, 12], true) ? $digits : '';
    }

    /** Host of an url or a domain of an email without "www.", lower case; '' when none. */
    public static function domainOf(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        if ($value === '') {
            return '';
        }
        if (strpos($value, '@') !== false && strpos($value, '://') === false) {
            $value = (string)substr((string)strrchr($value, '@'), 1);
        } else {
            $host = parse_url(strpos($value, '://') === false ? 'http://'.$value : $value, PHP_URL_HOST);
            $value = is_string($host) ? $host : '';
        }
        $value = preg_replace('/^www\./u', '', trim($value, '. '));

        return strpos((string)$value, '.') !== false ? (string)$value : '';
    }

    public function isPublicMailDomain(string $domain): bool
    {
        return in_array(mb_strtolower($domain, 'UTF-8'), $this->publicMailDomains, true);
    }

    private function finish(array $found): array
    {
        foreach ($found as &$candidate) {
            $candidate['reasons'] = array_values($candidate['reasons']);
            $candidate['strong'] = (bool)array_filter($candidate['reasons'], static fn($reason) => $reason['strong']);
            $candidate['score'] = 0;
            foreach ($candidate['reasons'] as $reason) {
                $candidate['score'] += $reason['strong'] ? 10 : 1;
            }
        }
        unset($candidate);
        uasort($found, static fn($a, $b) => [$b['score'], $a['type'] === self::TYPE_LEAD ? 1 : 0] <=> [$a['score'], $b['type'] === self::TYPE_LEAD ? 1 : 0]);

        return $found;
    }

    private function group(array $hits, bool $byNormalizedName = false): array
    {
        $result = [];
        foreach ($hits as $hit) {
            $key = $byNormalizedName ? $hit[3] : mb_strtolower((string)$hit[2], 'UTF-8');
            $result[$key][] = [$hit[0], $hit[1], $hit[2]];
        }

        return $result;
    }

    private function normalizeEmails(array $emails): array
    {
        $result = [];
        foreach ($emails as $email) {
            $email = mb_strtolower(trim((string)$email), 'UTF-8');
            if ($email !== '' && strpos($email, '@') !== false) {
                $result[$email] = $email;
            }
        }

        return array_values($result);
    }

    /** @return string[] significant digits => the phone as given */
    private function normalizePhones(array $phones): array
    {
        $result = [];
        foreach ($phones as $phone) {
            $digits = PhoneHelper::searchDigits((string)$phone);
            if (strlen($digits) >= 10) {
                $result[$digits] = trim((string)$phone);
            }
        }

        return $result;
    }

    private function companyDomains(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $domain = self::domainOf((string)$value);
            if ($domain !== '' && !$this->isPublicMailDomain($domain)) {
                $result[$domain] = $domain;
            }
        }

        return array_values($result);
    }

    /** @return array[] [type, id, matched value] */
    private function byEmails(array $emails, int $siteId): array
    {
        if (!$emails) {
            return [];
        }
        $result = [];
        foreach ($this->contactSources(true, $siteId) as [$type, $query]) {
            foreach ($query->andWhere(['in', new Expression('LOWER(c.value)'), $emails])->all() as $row) {
                $result[] = [$type, (int)$row['id'], mb_strtolower((string)$row['value'], 'UTF-8')];
            }
        }

        return $result;
    }

    /** @param string[] $phones digits => as given */
    private function byPhones(array $phones, int $siteId): array
    {
        if (!$phones) {
            return [];
        }
        $result = [];
        foreach ($this->contactSources(false, $siteId) as [$type, $query]) {
            $condition = ['or'];
            foreach ($phones as $phone) {
                if ($phoneCondition = PhoneHelper::equalCondition('c.value', $phone)) {
                    $condition[] = $phoneCondition;
                }
            }
            if (count($condition) === 1) {
                continue;
            }
            foreach ($query->andWhere($condition)->all() as $row) {
                $digits = PhoneHelper::searchDigits((string)$row['value']);
                // LIKE '%digits' also matches longer numbers: keep exact significant digits only.
                if (isset($phones[$digits])) {
                    $result[] = [$type, (int)$row['id'], $phones[$digits]];
                }
            }
        }

        return $result;
    }

    /**
     * Where emails or phones of leads, companies and clients are kept (a
     * client's own email and phone are rows of cms_user_email / cms_user_phone).
     * Leads: open ones only — a closed attempt is history, not a duplicate.
     *
     * @return array[] [type, Query selecting id and value from alias c]
     */
    private function contactSources(bool $email, int $siteId): array
    {
        $lead = $email ? CmsLeadEmail::tableName() : CmsLeadPhone::tableName();
        $company = $email ? CmsCompanyEmail::tableName() : CmsCompanyPhone::tableName();
        $user = $email ? CmsUserEmail::tableName() : CmsUserPhone::tableName();

        return [
            [self::TYPE_COMPANY, (new Query())->from(['c' => $company])->select(['id' => 'c.cms_company_id', 'value' => 'c.value'])],
            [self::TYPE_CLIENT, (new Query())->from(['c' => $user])->innerJoin(CmsUser::tableName().' u', 'u.id = c.cms_user_id')
                ->select(['id' => 'c.cms_user_id', 'value' => 'c.value'])->andWhere(['c.cms_site_id' => $siteId, 'u.is_worker' => 0])],
            [self::TYPE_LEAD, (new Query())->from(['c' => $lead])->innerJoin(CmsLead::tableName().' l', 'l.id = c.cms_lead_id')
                ->select(['id' => 'c.cms_lead_id', 'value' => 'c.value'])
                ->andWhere(['l.cms_site_id' => $siteId])->andFilterWhere(['l.status' => $this->leadStatuses])],
        ];
    }

    private function byInns(array $inns, int $siteId): array
    {
        if (!$inns) {
            return [];
        }
        $result = [];
        $maps = [
            [self::TYPE_COMPANY, '{{%cms_company2contractor}}', 'cms_company_id', null],
            [self::TYPE_CLIENT, '{{%cms_contractor_map}}', 'cms_user_id', null],
            [self::TYPE_LEAD, '{{%cms_lead2contractor}}', 'cms_lead_id', CmsLead::tableName()],
        ];
        foreach ($maps as [$type, $table, $column, $leadTable]) {
            $query = (new Query())->from(['m' => $table])->innerJoin('{{%cms_contractor}} c', 'c.id = m.cms_contractor_id')
                ->select(['id' => 'm.'.$column, 'inn' => 'c.inn'])->andWhere(['c.inn' => $inns]);
            if ($leadTable) {
                $query->innerJoin($leadTable.' l', 'l.id = m.cms_lead_id')
                    ->andWhere(['l.cms_site_id' => $siteId])->andFilterWhere(['l.status' => $this->leadStatuses]);
            }
            foreach ($query->all() as $row) {
                $result[] = [$type, (int)$row['id'], (string)$row['inn']];
            }
        }

        return $result;
    }

    /** Companies with a site (link) or a company email on the domain. Weak evidence. */
    private function byDomains(array $domains): array
    {
        if (!$domains) {
            return [];
        }
        $result = [];
        $emailLike = ['or'];
        $linkLike = ['or'];
        foreach ($domains as $domain) {
            $emailLike[] = ['like', new Expression('LOWER(value)'), '%@'.$domain, false];
            $linkLike[] = ['like', new Expression('LOWER(url)'), $domain];
        }
        foreach ((new Query())->from(CmsCompanyEmail::tableName())->select(['cms_company_id', 'value'])->andWhere($emailLike)->limit(200)->all() as $row) {
            $domain = self::domainOf((string)$row['value']);
            if (in_array($domain, $domains, true)) {
                $result[] = [self::TYPE_COMPANY, (int)$row['cms_company_id'], $domain];
            }
        }
        foreach ((new Query())->from(CmsCompanyLink::tableName())->select(['cms_company_id', 'url'])->andWhere($linkLike)->limit(200)->all() as $row) {
            $domain = self::domainOf((string)$row['url']);
            if (in_array($domain, $domains, true)) {
                $result[] = [self::TYPE_COMPANY, (int)$row['cms_company_id'], $domain];
            }
        }

        return $result;
    }

    /**
     * Companies by a normalized name. Compared in PHP after a coarse SQL
     * filter by the longest word: quotes, spaces and legal forms differ.
     *
     * @param string[] $names normalized => as given
     * @return array[] [type, id, value as given, normalized]
     */
    private function byNames(array $names, int $siteId): array
    {
        $result = [];
        $words = [];
        foreach (array_keys($names) as $name) {
            $longest = '';
            foreach (explode(' ', $name) as $word) {
                $longest = mb_strlen($word) > mb_strlen($longest) ? $word : $longest;
            }
            if (mb_strlen($longest) >= 3) {
                $words[$name] = $longest;
            }
        }
        foreach (array_chunk($words, 50, true) as $chunk) {
            $like = ['or'];
            foreach (array_unique($chunk) as $word) {
                // Also capitalized: not every database compares Cyrillic case-insensitively.
                $like[] = ['like', 'name', $word];
                $like[] = ['like', 'name', mb_convert_case($word, MB_CASE_TITLE, 'UTF-8')];
                $like[] = ['like', 'name', mb_strtoupper($word, 'UTF-8')];
            }
            $sources = [
                [self::TYPE_COMPANY, (new Query())->from(CmsCompany::tableName())->select(['id', 'name'])],
                [self::TYPE_LEAD, (new Query())->from(CmsLead::tableName())->select(['id', 'name'])
                    ->andWhere(['cms_site_id' => $siteId])->andFilterWhere(['status' => $this->leadStatuses])],
            ];
            foreach ($sources as [$type, $query]) {
                foreach ($query->andWhere($like)->all() as $row) {
                    $normalized = self::normalizeName((string)$row['name'], $this->legalForms);
                    if (isset($chunk[$normalized])) {
                        $result[] = [$type, (int)$row['id'], (string)$names[$normalized], $normalized];
                    }
                }
            }
        }

        return $result;
    }

    private function loadPhoneIndex(int $siteId): void
    {
        if ($this->_phoneIndex !== null && $this->_phoneIndexSite === $siteId) {
            return;
        }
        $this->_phoneIndex = [];
        $this->_phoneIndexSite = $siteId;
        foreach ($this->contactSources(false, $siteId) as [$type, $query]) {
            foreach ($query->each(1000) as $row) {
                $digits = PhoneHelper::searchDigits((string)$row['value']);
                if (strlen($digits) >= 10) {
                    $this->_phoneIndex[$digits][] = [$type, (int)$row['id']];
                }
            }
        }
    }

    private function siteId(): int
    {
        return \Yii::$app->has('skeeks') && \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : 0;
    }
}
