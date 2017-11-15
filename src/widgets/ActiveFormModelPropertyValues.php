<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.03.2015
 */

namespace skeeks\cms\widgets;

use skeeks\cms\base\widgets\ActiveFormAjaxSubmit;
use skeeks\modules\cms\form\models\Form;
use skeeks\widget\chosen\Chosen;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveForm
 * @package skeeks\modules\cms\form\widgets
 */
class ActiveFormModelPropertyValues extends ActiveFormAjaxSubmit
{
    /**
     * @var Model
     */
    public $modelWithProperties;

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

        echo \yii\helpers\Html::hiddenInput("sx-model-value", $this->modelWithProperties->id);
        echo \yii\helpers\Html::hiddenInput("sx-model", $this->modelWithProperties->className());
    }


    /**
     *
     * TODO: Вынести в трейт, используется для админки
     * Стилизованный селект админки
     *
     * @param $model
     * @param $attribute
     * @param $items
     * @param array $config
     * @param array $fieldOptions
     * @return \skeeks\cms\base\widgets\ActiveField
     */
    public function fieldSelect($model, $attribute, $items, $config = [], $fieldOptions = [])
    {
        $config = ArrayHelper::merge(
            ['allowDeselect' => false],
            $config,
            [
                'items' => $items,
            ]
        );

        foreach ($config as $key => $value) {
            if (property_exists(Chosen::className(), $key) === false) {
                unset($config[$key]);
            }
        }

        return $this->field($model, $attribute, $fieldOptions)->widget(
            Chosen::className(),
            $config
        );
    }
}
