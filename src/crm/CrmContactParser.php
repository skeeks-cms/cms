<?php

namespace skeeks\cms\crm;

use skeeks\cms\helpers\PhoneHelper;

/**
 * A line pasted from a table or a site — «Ромашка, +7 999 123-45-67,
 * info@romashka.ru, sales@romashka.ru» — split into a name, phones, emails,
 * INNs and sites. One function for the deal, company and client forms,
 * imports and API; its result is the input of CrmMatcher::rawFind() and the
 * fields of a new card.
 *
 * Parts are separated by a comma, a semicolon, a tab or a line break. A part
 * is an email, a phone (10+ significant digits with separators or a country
 * code), an INN (10 or 12 bare digits; a bare 10-digit number starting with 9
 * is a mobile phone), a site (a domain or an url), otherwise text: the first
 * text is the name, the next one the person.
 */
class CrmContactParser
{
    /**
     * @return array name, person, phones[], emails[], inns[], sites[], parts (count)
     */
    public static function parse(string $text): array
    {
        $result = ['name' => '', 'person' => '', 'phones' => [], 'emails' => [], 'inns' => [], 'sites' => [], 'parts' => 0];
        $parts = preg_split('/[,;\t\r\n]+/u', $text) ?: [];
        $texts = [];
        foreach ($parts as $part) {
            $part = trim($part, " \u{00A0}");
            // Quotes of a CSV cell ("…"), not the «» inside a company name.
            if (preg_match('/^"(.*)"$/su', $part, $quoted)) {
                $part = trim($quoted[1]);
            }
            if ($part === '') {
                continue;
            }
            $result['parts']++;
            // Several emails in one cell, "a@b.ru b@c.ru".
            if (preg_match_all('/[^\s<>()"\']+@[^\s<>()"\']+\.[a-zа-яё]{2,}/iu', $part, $emails) && $emails[0]) {
                foreach ($emails[0] as $email) {
                    $result['emails'][] = mb_strtolower($email, 'UTF-8');
                }
                $rest = trim(str_replace($emails[0], '', $part), " <>()");
                if ($rest !== '') {
                    $texts[] = $rest;
                }
                continue;
            }
            $digits = PhoneHelper::digits($part);
            $onlyDigits = preg_match('/^\d+$/', $part) === 1;
            if ($onlyDigits && (strlen($digits) === 12 || (strlen($digits) === 10 && $digits[0] !== '9'))) {
                $result['inns'][] = $digits;
                continue;
            }
            if (preg_match('/^(инн|inn)\s*:?\s*(\d{10}|\d{12})$/iu', $part, $match)) {
                $result['inns'][] = $match[2];
                continue;
            }
            if (PhoneHelper::isSearchablePhone($part) && strlen(PhoneHelper::searchDigits($part)) >= 10 && strlen($digits) <= 12) {
                $result['phones'][] = $part;
                continue;
            }
            if (preg_match('/^(https?:\/\/)?(www\.)?[a-z0-9а-яё-]+(\.[a-z0-9а-яё-]+)*\.[a-zа-яё]{2,}(\/\S*)?$/iu', $part)) {
                $result['sites'][] = $part;
                continue;
            }
            $texts[] = $part;
        }
        $result['name'] = $texts[0] ?? '';
        $result['person'] = $texts[1] ?? '';
        foreach (['phones', 'emails', 'inns', 'sites'] as $key) {
            $result[$key] = array_values(array_unique($result[$key]));
        }

        return $result;
    }

    /** The parsed line as CrmMatcher input. */
    public static function matcherInput(array $parsed, array $extra = []): array
    {
        return array_merge([
            'phones' => $parsed['phones'],
            'emails' => $parsed['emails'],
            'inns' => $parsed['inns'],
            'domains' => $parsed['sites'],
            'name' => $parsed['name'],
        ], $extra);
    }
}
