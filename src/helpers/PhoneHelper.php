<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\helpers;

use yii\db\Expression;

/**
 * Телефоны хранятся в международном формате libphonenumber: «+7 495 005-79-26».
 * Пользователи ищут их как угодно: «84950057926», «+7 (495) 005-79-26», «0057926».
 *
 * Хелпер приводит и запрос, и SQL-колонку к «только цифры», чтобы сравнение
 * не зависело от разделителей и от того, записан номер с 7, 8 или без кода.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class PhoneHelper
{
    /**
     * Символы, которые встречаются в записи номера и не влияют на его значение.
     */
    const SEPARATORS = [' ', '-', '(', ')', '+', '.', ',', "\t", "\xC2\xA0"];

    /**
     * Меньше этого количества цифр строка не считается телефоном:
     * иначе любой короткий номер дома или ИНН превращается в поиск по телефонам.
     */
    const MIN_SEARCH_DIGITS = 4;

    /**
     * @param string|null $value
     * @return string только цифры
     */
    public static function digits($value)
    {
        return (string)preg_replace('/\D+/u', '', (string)$value);
    }

    /**
     * Строка похожа на телефон, если кроме цифр в ней только разделители.
     *
     * @param string|null $value
     * @return bool
     */
    public static function isSearchablePhone($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return false;
        }

        $rest = str_replace(self::SEPARATORS, '', $value);
        if ($rest === '' || !ctype_digit($rest)) {
            return false;
        }

        return mb_strlen($rest) >= self::MIN_SEARCH_DIGITS;
    }

    /**
     * Значимая часть номера: без кода страны и без междугородней «восьмёрки».
     *
     * «+7 495 005-79-26», «84950057926» и «74950057926» дают «4950057926».
     *
     * @param string|null $value
     * @return string
     */
    public static function searchDigits($value)
    {
        $digits = self::digits($value);

        if (strlen($digits) >= 11 && ($digits[0] === '7' || $digits[0] === '8')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    /**
     * SQL-выражение, приводящее колонку с телефоном к «только цифры».
     *
     * @param string $column имя колонки или её алиас, значение задаётся кодом, не пользователем
     * @return string
     */
    public static function sqlDigits($column)
    {
        $expression = $column;
        foreach (self::SEPARATORS as $separator) {
            $expression = "REPLACE({$expression}, '".addslashes($separator)."', '')";
        }

        return $expression;
    }

    /**
     * Условие «в колонке есть такой номер» независимо от формата записи.
     *
     * @param string $column
     * @param string $value
     * @return array|null null, если строка не похожа на телефон
     */
    public static function likeCondition($column, $value)
    {
        if (!self::isSearchablePhone($value)) {
            return null;
        }

        $digits = self::searchDigits($value);
        if ($digits === '') {
            return null;
        }

        return ['like', new Expression(self::sqlDigits($column)), $digits];
    }

    /**
     * Условие «это тот же самый номер» независимо от формата записи.
     *
     * Номер в базе хранится с кодом страны, а приходит он из телефонии или из
     * формы входа как угодно, поэтому сравнивается значимая часть номера.
     *
     * @param string $column
     * @param string $value
     * @return array|null null, если строка не похожа на телефон
     */
    public static function equalCondition($column, $value)
    {
        if (!self::isSearchablePhone($value)) {
            return null;
        }

        $digits = self::searchDigits($value);
        if (strlen($digits) < 10) {
            return null;
        }

        return ['like', new Expression(self::sqlDigits($column)), '%'.$digits, false];
    }
}
