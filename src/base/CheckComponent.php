<?php
/**
 *
 * Проверка чего либо.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\models\Settings;
use yii\base\Component as YiiComponent;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class CheckComponent
 * @package skeeks\cms\base
 */
abstract class CheckComponent extends \yii\base\Component
{
    const RESULT_SUCCESS        = 'success';
    const RESULT_ERROR          = 'error';
    const RESULT_WARNING        = 'warning';

    public $name                = '';
    public $description         = "";

    public $successText         = "";
    public $errorText           = "";
    public $warningText         = "";

    public $result                  = self::RESULT_SUCCESS;

    public $errorMessages           = [];
    public $successMessages         = [];
    public $warningMessages         = [];

    /**
     * @var int процент выполненности задания, длинные задачи можно делать в несколько запросов.
     */
    public $ptc                     = 100;
    public $lastValue               = null;

    abstract public function run();

    public function init()
    {
        if(!$this->name)
        {
            $this->name = \Yii::t('skeeks/cms','Checking the necessary modules');
        }
        if(!$this->description)
        {
            $this->description = \Yii::t('skeeks/cms','Checking the availability of the required extensions for maximality work product. If an error occurs, show a list of modules that are unavailable.');
        }
        if(!$this->successText)
        {
            $this->successText = \Yii::t('skeeks/cms','All necessary modules are installed');
        }
        if(!$this->errorText)
        {
            $this->errorText = \Yii::t('skeeks/cms','Not all modules are installed');
        }
        if(!$this->warningText)
        {
            $this->warningText = \Yii::t('skeeks/cms','Some non-critical remark');
        }

        parent::init();
    }

    /**
     * @param string $message
     * @return $this
     */
    public function addError($message = "")
    {
        $this->result               = self::RESULT_ERROR;
        $this->errorMessages[]      = $message;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function addWarning($message = "")
    {
        $this->result = self::RESULT_WARNING;
        $this->warningMessages[]      = $message;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function addSuccess($message = "")
    {
        $this->result = self::RESULT_SUCCESS;
        $this->successMessages[]      = $message;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return ($this->result == self::RESULT_SUCCESS);
    }
}