<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.03.2015
 */
namespace skeeks\cms\base\widgets;
use skeeks\cms\traits\ActiveFormAjaxSubmitTrait;

/**
 * Class ActiveFormAjaxSubmit
 * @package skeeks\cms\base\widgets
 */
class ActiveFormAjaxSubmit extends ActiveForm
{
    use ActiveFormAjaxSubmitTrait;
    public $afterValidateCallback = "";

    public function __construct($config = [])
    {
        $this->enableAjaxValidation         = true;
        parent::__construct($config);
    }
}
