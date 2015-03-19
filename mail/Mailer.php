<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.03.2015
 */
namespace skeeks\cms\mail;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class Mailer
 * @package skeeks\cms\mail
 */
class Mailer extends \yii\swiftmailer\Mailer
{
    /**
     * @var array
     */
    public $tagStyles =
    [
        'h1' => 'color:#1D5800;font-size:32px;font-weight:normal;margin-bottom:13px;margin-top:20px;',
        'h2' => 'color:#1D5800;font-size:28px;font-weight:normal;margin-bottom:10px;margin-top:17px;',
        'h3' => 'color:#1D5800;font-size:24px;font-weight:normal;margin-bottom:7px;margin-top:14px;',
        'h4' => 'color:#1D5800;font-size:20px;font-weight:normal;margin-bottom:6px;margin-top:11px;',
        'h5' => 'color:#1D5800;font-size:16px;font-weight:normal;margin-bottom:6px;margin-top:8px;',
        'p' => 'font:Arial,Helvetica,sans-serif;',
    ];
}