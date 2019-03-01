<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.07.2015
 */

namespace skeeks\cms\helpers;

/**
 * Class StringHelper
 * @package skeeks\cms\helpers
 */
class StringHelper
{

    /**
     *
     * Замена только первого вхождения строки в подстроку
     *
     * @param $search
     * @param $replace
     * @param $text
     * @return mixed
     */
    public static function strReplaceOnce($search, $replace, $text)
    {
        $pos = strpos($text, $search);
        return $pos !== false ? substr_replace($text, $replace, $pos, strlen($search)) : $text;
    }

    /**
     *
     * Склонение слов
     *
     * @static
     * @param $number |   число
     * @param $suffix |   array("минута", "минуты", "минут");
     * @return mixed
     */
    public static function declination($number, $suffix)
    {
        $keys = array(2, 0, 1, 1, 1, 2);
        $mod = $number % 100;
        $suffix_key = ($mod > 7 && $mod < 20) ? 2 : $keys[min($mod % 10, 5)];
        return $suffix[$suffix_key];
    }

    /**
     * @param $string
     * @return string
     */
    public static function strtolower($string)
    {
        return mb_strtolower($string, \Yii::$app->charset);
    }


    /**
     * @param $string
     * @return string
     */
    public static function strtoupper($string)
    {
        return mb_strtoupper($string, \Yii::$app->charset);
    }

    /**
     * @param $string
     * @return string
     */
    public static function ucfirst($string)
    {
        $str = $string;
        $encoding = \Yii::$app->charset;
        return mb_substr(mb_strtoupper($str, $encoding), 0, 1, $encoding) . mb_substr($str, 1, mb_strlen($str) - 1,
                $encoding);
    }

    /**
     * @param $string
     * @return string
     */
    public static function lcfirst($string)
    {
        $str = $string;
        $encoding = \Yii::$app->charset;
        return mb_substr(mb_strtolower($str, $encoding), 0, 1, $encoding) . mb_substr($str, 1, mb_strlen($str) - 1,
                $encoding);
    }

    /**
     * @param $string
     * @return int
     */
    public static function strlen($string)
    {
        $str = $string;
        $encoding = \Yii::$app->charset;
        return mb_strlen($str, $encoding);
    }


    /**
     * @param $str
     * @param $start
     * @param null $length
     * @return string
     */
    public static function substr($str, $start, $length = null)
    {
        $encoding = \Yii::$app->charset;
        return mb_substr($str, $start, $length, $encoding);
    }


    /**
     * @param $str
     * @param $start
     * @param null $length
     * @return string
     */
    public static function htmlspecialchars($str, $start, $length = null)
    {
        $encoding = \Yii::$app->charset;
        return htmlspecialchars($str, $start, $length, $encoding);
    }


    /**
     * @param $data
     * @return string
     */
    public static function compressBase64EncodeUrl($data)
    {
        return rtrim(strtr(base64_encode(
            gzcompress(serialize($data), 9)
        ), '+/', '-_'), '=');
    }

    /**
     * @param $string
     * @return mixed
     */
    public static function compressBase64DecodeUrl($string)
    {
        return unserialize(gzuncompress(base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=',
            STR_PAD_RIGHT))));
    }


    /**
     * @param $data
     * @return string
     */
    public static function base64EncodeUrl($string)
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    /**
     * @param $string
     * @return mixed
     */
    public static function base64DecodeUrl($string)
    {
        return base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=', STR_PAD_RIGHT));
    }
}