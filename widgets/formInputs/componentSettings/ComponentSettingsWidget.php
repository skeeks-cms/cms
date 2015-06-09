<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
namespace skeeks\cms\widgets\formInputs\componentSettings;

use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\modules\admin\Module;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use skeeks\widget\ckeditor\CKEditorWidgetAsset;
use skeeks\widget\codemirror\CodemirrorWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class ComponentSettingsWidget
 * @package skeeks\cms\widgets\formInputs\componentSettings
 */
class ComponentSettingsWidget extends InputWidget
{
    /**
     * @var array Общие js опции текущего виджета
     */
    public $clientOptions = [];

    /**
     * @var string ID селекта компонентов
     */
    public $componentSelectId = "";

    /**
	 * @inheritdoc
	 */
	public function run()
	{
        if ($this->hasModel())
        {
            $name   = Html::getInputName($this->model, $this->attribute);
            $value  = \skeeks\sx\String::base64EncodeUrl(serialize((array) $this->model->{$this->attribute}));

            $this->options['id'] = Html::getInputId($this->model, $this->attribute);

			//$element = Html::activeHiddenInput($this->model, $this->attribute, $this->options);
            $element = Html::hiddenInput($name, $value, $this->options);
		} else
        {
            $element = Html::hiddenInput($this->name, $this->value, $this->options);
		}

        $this->registerPlugin();

        $this->clientOptions['componentSelectId']       = $this->componentSelectId;
        $this->clientOptions['componentSettingsId']     = Html::getInputId($this->model, $this->attribute);
        $this->clientOptions['id']                      = $this->id;
        $this->clientOptions['backend']                 = UrlHelper::construct('/cms/admin-universal-component-settings/index')
                                                            ->setSystemParam(Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                                                            ->enableAdmin()
                                                            ->toString();

        return $this->render('element', [
            'widget'    => $this,
            'element'   => $element
        ]);
	}



    /**
	 * Registers CKEditor plugin
	 */
	protected function registerPlugin()
	{
        ComponentSettingsWidgetAsset::register($this->view);
	}
}

