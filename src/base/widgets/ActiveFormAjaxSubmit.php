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
 *
 *
 * 'afterValidateCallback'     => new \yii\web\JsExpression(<<<JS
 * function(jForm, AjaxQuery)
 * {
 * var Handler = new sx.classes.AjaxHandlerStandartRespose(AjaxQuery);
 * var Blocker = new sx.classes.AjaxHandlerBlocker(AjaxQuery, {
 * 'wrapper' : jForm.closest('.modal-content')
 * });
 *
 * Handler.bind('success', function()
 * {
 * _.delay(function()
 * {
 * window.location.reload();
 * }, 1000);
 * });
 * }
 * JS
 * )
 * JS
 * )
 *
 * Class ActiveFormAjaxSubmit
 * @package skeeks\cms\base\widgets
 */
class ActiveFormAjaxSubmit extends ActiveForm
{
    use ActiveFormAjaxSubmitTrait;

    public $afterValidateCallback = "";
    public $enableAjaxValidation = true;
}
