<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\userPropertyTypes;

use skeeks\cms\components\Cms;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\widgets\ColorInput;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * Class UserPropertyTypeColor
 * @package skeeks\cms\relatedProperties\userPropertyTypes
 */
class UserPropertyTypeColor extends PropertyType
{
    public $code = self::CODE_STRING;
    public $name = "";


    public $showDefaultPalette = Cms::BOOL_Y;
    public $saveValueAs = Cms::BOOL_Y;
    public $useNative = Cms::BOOL_N;

    public $showAlpha = Cms::BOOL_Y;
    public $showInput = Cms::BOOL_Y;
    public $showPalette = Cms::BOOL_Y;

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'Choice of color');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                'showDefaultPalette' => \Yii::t('skeeks/cms', 'Show extended palette of colors'),
                'saveValueAs'        => \Yii::t('skeeks/cms', 'Format conservation values'),
                'useNative'          => \Yii::t('skeeks/cms', 'Use the native color selection'),
                'showAlpha'          => \Yii::t('skeeks/cms', 'Management transparency'),
                'showInput'          => \Yii::t('skeeks/cms', 'Show input field values'),
                'showPalette'        => \Yii::t('skeeks/cms', 'Generally show the palette'),
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
                ['showDefaultPalette', 'string'],
                ['useNative', 'string'],
                ['showAlpha', 'string'],
                ['showInput', 'string'],
                ['showPalette', 'string'],
                [
                    ['showDefaultPalette', 'useNative', 'showAlpha', 'showInput', 'showPalette'],
                    'in',
                    'range' => array_keys(\Yii::$app->cms->booleanFormat()),
                ],

                ['saveValueAs', 'string'],
                ['saveValueAs', 'in', 'range' => array_keys(ColorInput::$possibleSaveAs)],
            ]);
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $field = parent::renderForActiveForm($relatedPropertiesModel);

        $pluginOptions = [
            'showAlpha'   => (bool)($this->showAlpha === Cms::BOOL_Y),
            'showInput'   => (bool)($this->showInput === Cms::BOOL_Y),
            'showPalette' => (bool)($this->showPalette === Cms::BOOL_Y),
        ];

        $field->widget(ColorInput::className(), [
            'showDefaultPalette' => (bool)($this->showDefaultPalette === Cms::BOOL_Y),
            'useNative'          => (bool)($this->useNative === Cms::BOOL_Y),
            'saveValueAs'        => (string)$this->saveValueAs,
            'pluginOptions'      => $pluginOptions,
        ]);

        return $field;
    }


    /**
     * @return string
     */
    public function renderConfigFormFields(ActiveForm $activeForm)
    {
        $result = $activeForm->field($this, 'showDefaultPalette')->checkbox([
            'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
            'value'   => \skeeks\cms\components\Cms::BOOL_Y,
        ]);
        $result .= $activeForm->field($this, 'useNative')->checkbox([
            'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
            'value'   => \skeeks\cms\components\Cms::BOOL_Y,
        ]);
        $result .= $activeForm->field($this, 'showInput')->hint(\Yii::t('skeeks/cms',
            'This INPUT to opened the palette'))->checkbox([
            'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
            'value'   => \skeeks\cms\components\Cms::BOOL_Y,
        ]);
        $result .= $activeForm->field($this, 'showAlpha')->checkbox([
            'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
            'value'   => \skeeks\cms\components\Cms::BOOL_Y,
        ]);
        $result .= $activeForm->field($this, 'showPalette')->checkbox([
            'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
            'value'   => \skeeks\cms\components\Cms::BOOL_Y,
        ]);
        $result .= $activeForm->field($this, 'saveValueAs')->radioList(\skeeks\cms\widgets\ColorInput::$possibleSaveAs);

        return $result;
    }

    /**
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $relatedPropertiesModel->addRule($this->property->code, 'string');

        return $this;
    }
}