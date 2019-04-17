<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.03.2015
 */

namespace skeeks\cms\widgets;

use skeeks\cms\modules\admin\traits\ActiveFormTrait;
use skeeks\cms\modules\admin\traits\AdminActiveFormTrait;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\traits\ActiveFormAjaxSubmitTrait;
use skeeks\modules\cms\form\models\Form;
use yii\base\Model;

/**
 * Class ActiveFormRelatedProperties
 * @package skeeks\cms\widgets
 */
class ActiveFormRelatedProperties extends ActiveForm
{
    use AdminActiveFormTrait;
    use ActiveFormAjaxSubmitTrait;

    /**
     * @var Model
     */
    public $modelHasRelatedProperties;

    public function __construct($config = [])
    {
        $this->validationUrl = \skeeks\cms\helpers\UrlHelper::construct('cms/model-properties/validate')->toString();
        $this->action = \skeeks\cms\helpers\UrlHelper::construct('cms/model-properties/submit')->toString();

        $this->enableAjaxValidation = true;

        parent::__construct($config);
    }

    public function init()
    {
        parent::init();

        echo \yii\helpers\Html::hiddenInput("sx-model-value", $this->modelHasRelatedProperties->id);
        echo \yii\helpers\Html::hiddenInput("sx-model", $this->modelHasRelatedProperties->className());
    }
}
