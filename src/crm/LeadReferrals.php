<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadReferral;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * «Откуда узнали» (owner's decision 05.11.2026): where a client learned about
 * the company — a site list (CmsLeadReferral) the client keeps himself, and the
 * deal field `cms_lead.cms_lead_referral_id`. The channel (`source_type`) says
 * how the request reached the CRM; this says what advertising brought it.
 *
 * Filling. Only an empty field is filled; a choice of a person is never
 * overwritten. CmsLead::beforeSave() fills a new deal (forms, letters,
 * campaigns, API); CmsTelephonyCallLinkService fills a deal without an item
 * from the number an incoming call was made to. Order:
 *
 * 1. EVENT_DETECT (LeadReferralEvent): a package names the item by its own
 *    evidence; the core does not depend on any package;
 * 2. rules without `referrer` — marks of the visit (utm_*, yclid and other
 *    remembered parameters), the channel, the called number;
 * 3. only when the visit has no utm marks — rules with `referrer`, the first
 *    external page of the visit (`sx_first_referrer` of cms-seo, or
 *    `source_data.referrer`).
 *
 * Within a step the rule with more conditions wins, then the longer referrer
 * pattern ("yandex.ru/maps" over "yandex.ru"), then the order of the list.
 *
 * Rules of an item: one rule per line, conditions of a line separated by
 * spaces, all must hold; `key=value1,value2` — any of the values; `*` — any
 * non-empty value, `yandex*` — a prefix. Keys: utm_source … utm_term or any
 * remembered parameter (yclid, from, …), `referrer` (a domain with its
 * subdomains, optionally with a path prefix), `channel` (source_type),
 * `phone` (the called number, the last 10 digits). Case is ignored; `#`
 * starts a comment.
 */
class LeadReferrals
{
    /** Before the built-in rules: LeadReferralEvent. */
    public const EVENT_DETECT = 'detect';

    public const KEY_REFERRER = 'referrer';
    public const KEY_CHANNEL = 'channel';
    public const KEY_PHONE = 'phone';

    /** Parameters of a visit kept by cms-seo that are not marks. */
    private const SKIP_PARAMS = ['created_at', 'sx_first_referrer'];

    /**
     * The starting list of a site: code => [name, sort, rules].
     * Kept equal to the list of the migration m261105_100000.
     */
    public static function defaults(): array
    {
        return [
            'yandex_direct' => ['Яндекс Директ', 10, "utm_source=yandex,yandex_direct,direct utm_medium=cpc,cpm,cpa,paid\nyclid=*"],
            'seo' => ['Поиск (SEO)', 20, "utm_medium=organic\nreferrer=yandex.ru,ya.ru,yandex.com,yandex.by,yandex.kz,google.com,google.ru,bing.com,go.mail.ru,duckduckgo.com,search.yahoo.com,nova.rambler.ru"],
            'yandex_maps' => ['Яндекс Карты', 30, "utm_source=yandex_maps,yandexmaps,ymaps\nreferrer=yandex.ru/maps,yandex.com/maps,yandex.by/maps,maps.yandex.ru,yandex.ru/profile"],
            '2gis' => ['2ГИС', 40, "utm_source=2gis\nreferrer=2gis.ru,2gis.com,2gis.kz"],
            'avito' => ['Авито', 50, "utm_source=avito\nreferrer=avito.ru"],
            'social' => ['Соцсети', 60, "utm_source=vk,vkontakte,telegram,tg,ok,odnoklassniki,instagram,facebook,dzen\nreferrer=vk.com,vk.ru,ok.ru,t.me,instagram.com,facebook.com,dzen.ru"],
            'signboard' => ['Вывеска / проходил мимо', 70, null],
            'recommendation' => ['Рекомендация', 80, null],
            'repeat' => ['Повторный клиент', 90, null],
            'mailing' => ['Рассылка', 100, "utm_medium=email\nchannel=mailing"],
            'other' => ['Другое', 110, null],
        ];
    }

    /**
     * Adds the missing items of the starting list to a site (by code and by name). Returns how many were added.
     */
    public static function ensureDefaults(int $siteId): int
    {
        $added = 0;
        foreach (self::defaults() as $code => [$name, $sort, $rules]) {
            $exists = CmsLeadReferral::find()->andWhere(['cms_site_id' => $siteId])
                ->andWhere(['or', ['code' => $code], ['name' => $name]])->exists();
            if ($exists) {
                continue;
            }
            $item = new CmsLeadReferral([
                'cms_site_id' => $siteId,
                'name' => $name,
                'code' => $code,
                'sort' => $sort,
                'is_active' => 1,
                'match_rules' => $rules,
            ]);
            if ($item->save()) {
                $added++;
            }
        }

        return $added;
    }

    /**
     * Fills an empty «Откуда узнали» of a deal; returns whether a value was set. `$extra['phone']` — the number
     * a call was made to. Never throws except a lock conflict (the owner of the transaction repeats it).
     */
    public static function fill(CmsLead $lead, array $extra = []): bool
    {
        if (!CmsLead::referralReady() || $lead->cms_lead_referral_id) {
            return false;
        }
        try {
            $found = self::detect($lead, $extra);
        } catch (\Throwable $e) {
            if ($e instanceof \yii\db\Exception && \skeeks\cms\services\CmsLeadService::isLockConflict($e)) {
                throw $e;
            }
            \Yii::error('«Откуда узнали» не определено: '.$e->getMessage(), self::class);
            return false;
        }
        if (!$found) {
            return false;
        }
        $lead->cms_lead_referral_id = $found['id'];
        $lead->referral_note = mb_substr($found['note'], 0, 255);

        return true;
    }

    /**
     * Call tracking: an incoming call linked to a deal without «Откуда узнали» fills it by the number the client
     * called (rules `phone=`, or a package by EVENT_DETECT with `context.phone`). Only the called number counts
     * here — marks and the channel of an older deal were looked at when it was created. Saved at once with an
     * entry in the deal feed. Returns whether a value was set.
     */
    public static function fillByCall(CmsLead $lead, string $calledNumber): bool
    {
        $phone = self::phoneKey($calledNumber);
        if (!CmsLead::referralReady() || $lead->cms_lead_referral_id || $phone === '') {
            return false;
        }
        try {
            $found = self::detect($lead, [], ['params' => [], 'referrer' => '', 'channel' => '', 'phone' => $phone]);
            if (!$found) {
                return false;
            }
            $lead->updateAttributes([
                'cms_lead_referral_id' => $found['id'],
                'referral_note' => mb_substr($found['note'], 0, 255),
            ]);
            $item = CmsLeadReferral::findOne($found['id']);
            $lead->addSystemActivity('Откуда узнали: «'.\yii\helpers\Html::encode($item ? $item->name : '').'» — '
                .\yii\helpers\Html::encode(mb_strtolower(preg_replace('/^Определено автоматически:\s*/u', '', $found['note']))));
        } catch (\Throwable $e) {
            if ($e instanceof \yii\db\Exception && \skeeks\cms\services\CmsLeadService::isLockConflict($e)) {
                throw $e;
            }
            \Yii::error('«Откуда узнали» по звонку не определено: '.$e->getMessage(), self::class);
            return false;
        }

        return true;
    }

    /**
     * The item of a deal by the event and the rules: ['id' => int, 'note' => string] or null. `$context` replaces
     * the one built from the deal (fillByCall()).
     */
    public static function detect(CmsLead $lead, array $extra = [], ?array $context = null): ?array
    {
        $siteId = (int)($lead->cms_site_id ?: (\Yii::$app->skeeks->site->id ?? 0));
        $items = $siteId ? CmsLeadReferral::activeForSite($siteId) : [];
        $context = $context ?? self::context($lead, $extra);

        $event = new LeadReferralEvent(['lead' => $lead, 'context' => $context]);
        Event::trigger(self::class, self::EVENT_DETECT, $event);
        if ($event->referralId || $event->code) {
            foreach ($items as $item) {
                if (($event->referralId && (int)$item->id === (int)$event->referralId)
                    || ($event->code && (string)$item->code !== '' && mb_strtolower((string)$item->code) === mb_strtolower($event->code))) {
                    return ['id' => (int)$item->id, 'note' => $event->note];
                }
            }
        }
        if ($event->handled || !$items) {
            return null;
        }

        return self::matchItems($items, $context);
    }

    /**
     * Picks the item by the rules (steps 2 and 3 of the class description).
     *
     * @param CmsLeadReferral[] $items in list order
     */
    public static function matchItems(array $items, array $context): ?array
    {
        $hasMarks = false;
        foreach (CmsLead::UTM_ATTRIBUTES as $attribute) {
            if (($context['params'][$attribute] ?? '') !== '') {
                $hasMarks = true;
            }
        }
        foreach ([false, true] as $byReferrer) {
            if ($byReferrer && ($hasMarks || ($context['referrer'] ?? '') === '')) {
                break;
            }
            $best = null;
            foreach (array_values($items) as $position => $item) {
                foreach (self::parseRules((string)$item->match_rules) as $rule) {
                    if (isset($rule[self::KEY_REFERRER]) !== $byReferrer) {
                        continue;
                    }
                    $hit = self::matchRule($rule, $context);
                    if ($hit === null) {
                        continue;
                    }
                    $score = [count($rule), $hit['length'], -$position];
                    if ($best === null || $score > $best['score']) {
                        $best = ['score' => $score, 'id' => (int)$item->id, 'note' => $hit['note']];
                    }
                }
            }
            if ($best) {
                return ['id' => $best['id'], 'note' => $best['note']];
            }
        }

        return null;
    }

    /**
     * What the rules look at: `params` (lowercase name => value: the utm columns, the remembered parameters of
     * the visit, the query of the source page), `referrer` (the first external page), `channel`, `phone`.
     */
    public static function context(CmsLead $lead, array $extra = []): array
    {
        $params = [];
        $data = $lead->source_data;
        if (is_string($data) && $data !== '') {
            // In beforeSave() HasJsonFieldsBehavior has already encoded the field.
            $data = json_decode($data, true);
        }
        $data = is_array($data) ? $data : [];
        $url = trim((string)$lead->source_url);
        if ($url !== '') {
            $query = parse_url($url, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                parse_str($query, $fromUrl);
                foreach ($fromUrl as $name => $value) {
                    if (is_scalar($value)) {
                        $params[mb_strtolower((string)$name)] = trim((string)$value);
                    }
                }
            }
        }
        foreach ((is_array($data['utm'] ?? null) ? $data['utm'] : []) as $name => $value) {
            $name = mb_strtolower((string)$name);
            if (is_scalar($value) && !in_array($name, self::SKIP_PARAMS, true)) {
                $params[in_array('utm_'.$name, CmsLead::UTM_ATTRIBUTES, true) ? 'utm_'.$name : $name] = trim((string)$value);
            }
        }
        foreach (CmsLead::UTM_ATTRIBUTES as $attribute) {
            if (trim((string)$lead->getAttribute($attribute)) !== '') {
                $params[$attribute] = trim((string)$lead->getAttribute($attribute));
            }
        }
        $params = array_filter($params, static fn($value) => $value !== '');

        $referrer = '';
        foreach ([$data['utm']['sx_first_referrer'] ?? null, $data['referrer'] ?? null, $extra['referrer'] ?? null] as $candidate) {
            if (is_string($candidate) && self::isExternal($candidate, $url)) {
                $referrer = trim($candidate);
                break;
            }
        }

        return [
            'params' => $params,
            'referrer' => $referrer,
            'channel' => (string)$lead->source_type,
            'phone' => self::phoneKey((string)($extra['phone'] ?? '')),
        ];
    }

    /**
     * Rules of an item: a list of rules, each `key => [values]`.
     *
     * @return array[]
     */
    public static function parseRules(string $text): array
    {
        $rules = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/#.*$/u', '', $line));
            if ($line === '') {
                continue;
            }
            $rule = [];
            foreach (self::conditions($line) as $condition) {
                if (!preg_match('/^([a-z0-9_]+)=(.+)$/iu', $condition, $m)) {
                    $rule = null;
                    break;
                }
                $values = array_values(array_filter(array_map(static fn($v) => mb_strtolower(trim($v)), explode(',', $m[2])), 'strlen'));
                if (!$values) {
                    $rule = null;
                    break;
                }
                $rule[mb_strtolower($m[1])] = $values;
            }
            if ($rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Conditions of a line: a new one starts at a space before `name=`, so a value may hold spaces
     * (`phone=+7 900 000-00-00`).
     *
     * @return string[]
     */
    private static function conditions(string $line): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\s+(?=[a-z0-9_]+=)/iu', $line)), 'strlen'));
    }

    /** A readable error of the rules text or null. */
    public static function validateRules(string $text): ?string
    {
        foreach (preg_split('/\R/u', $text) as $number => $line) {
            $line = trim(preg_replace('/#.*$/u', '', $line));
            if ($line === '') {
                continue;
            }
            foreach (self::conditions($line) as $condition) {
                if (!preg_match('/^([a-z0-9_]+)=([^=]+)$/iu', $condition, $m) || trim(str_replace(',', '', $m[2])) === '') {
                    return 'Строка '.($number + 1).': «'.$condition.'» — нужно имя=значение, например utm_source=yandex.';
                }
                if (mb_strtolower($m[1]) === self::KEY_CHANNEL) {
                    foreach (explode(',', mb_strtolower($m[2])) as $channel) {
                        if (trim($channel) !== '' && !array_key_exists(trim($channel), CmsLead::sources())) {
                            return 'Строка '.($number + 1).': канала «'.trim($channel).'» нет. Каналы: '.implode(', ', array_keys(CmsLead::sources())).'.';
                        }
                    }
                }
            }
        }

        return null;
    }

    /** The line of the card under an automatic value. */
    public static function noteOf(CmsLead $lead): string
    {
        return CmsLead::referralReady() ? trim((string)$lead->referral_note) : '';
    }

    /**
     * Whether a rule holds: ['note' => why, 'length' => the referrer pattern length] or null.
     */
    private static function matchRule(array $rule, array $context): ?array
    {
        $notes = [];
        $length = 0;
        foreach ($rule as $key => $values) {
            if ($key === self::KEY_REFERRER) {
                $hit = self::matchReferrer($values, (string)$context['referrer']);
                if ($hit === null) {
                    return null;
                }
                $length = mb_strlen($hit);
                $notes[] = 'переход с '.(string)(parse_url($context['referrer'], PHP_URL_HOST) ?: $hit);
            } elseif ($key === self::KEY_CHANNEL) {
                if (!in_array(mb_strtolower((string)$context['channel']), $values, true)) {
                    return null;
                }
                $notes[] = 'канал «'.(CmsLead::sources()[$context['channel']] ?? $context['channel']).'»';
            } elseif ($key === self::KEY_PHONE) {
                $phone = (string)$context['phone'];
                $match = $phone !== '' && in_array($phone, array_map([self::class, 'phoneKey'], $values), true);
                if (!$match) {
                    return null;
                }
                $notes[] = 'звонок на номер '.$phone;
            } else {
                $value = mb_strtolower((string)($context['params'][$key] ?? ''));
                if ($value === '' || !self::matchValue($values, $value)) {
                    return null;
                }
                $notes[] = $key.'='.$value;
            }
        }

        return ['note' => 'Определено автоматически: '.implode(', ', $notes), 'length' => $length];
    }

    private static function matchValue(array $patterns, string $value): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === '*' || $pattern === $value) {
                return true;
            }
            if (strpos($pattern, '*') !== false) {
                $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/u';
                if (preg_match($regex, $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** The matched pattern of `referrer=` or null. */
    private static function matchReferrer(array $patterns, string $referrer): ?string
    {
        $host = mb_strtolower((string)parse_url($referrer, PHP_URL_HOST));
        $host = preg_replace('/^www\./u', '', $host);
        $path = mb_strtolower((string)parse_url($referrer, PHP_URL_PATH));
        if ($host === '') {
            return null;
        }
        $best = null;
        foreach ($patterns as $pattern) {
            $pattern = preg_replace('~^https?://~u', '', $pattern);
            $parts = explode('/', $pattern, 2);
            $domain = preg_replace('/^www\./u', '', $parts[0]);
            $prefix = isset($parts[1]) ? '/'.trim($parts[1], '/') : '';
            $hostOk = $host === $domain || mb_substr($host, -mb_strlen($domain) - 1) === '.'.$domain;
            $pathOk = $prefix === '' || $path === $prefix || strpos($path, $prefix.'/') === 0;
            if ($hostOk && $pathOk && ($best === null || mb_strlen($pattern) > mb_strlen($best))) {
                $best = $pattern;
            }
        }

        return $best;
    }

    /** A page of another site than the source page. */
    private static function isExternal(string $url, string $sourceUrl): bool
    {
        $host = mb_strtolower((string)parse_url(trim($url), PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        $own = array_filter([
            mb_strtolower((string)parse_url($sourceUrl, PHP_URL_HOST)),
            \Yii::$app->request instanceof \yii\web\Request ? mb_strtolower((string)\Yii::$app->request->hostName) : '',
        ]);
        $strip = static fn(string $h) => preg_replace('/^www\./u', '', $h);

        return !in_array($strip($host), array_map($strip, $own), true);
    }

    /** The last 10 digits of a phone. */
    public static function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    /** Items of a site for a select: id => name; the inactive current value is kept. */
    public static function items(?int $siteId = null, ?int $keepId = null): array
    {
        if (!CmsLead::referralReady()) {
            return [];
        }
        $items = ArrayHelper::map(CmsLeadReferral::activeForSite($siteId), 'id', 'name');
        if ($keepId && !isset($items[$keepId]) && ($kept = CmsLeadReferral::findOne($keepId))) {
            $items[$keepId] = $kept->name.' (выключен)';
        }

        return $items;
    }
}
