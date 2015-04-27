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

    public $name                = 'Проверка необходимых модулей';
    public $description         = "Проверяется доступность требуемых расширений для полноценной работы продукта. В случае ошибки выводится список модулей, которые недоступны.";

    public $successText         = "Все необходимые модули установлены";
    public $errorText           = "Не все модули установлены";
    public $warningText         = "Какое то некритичное замечание";

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
}