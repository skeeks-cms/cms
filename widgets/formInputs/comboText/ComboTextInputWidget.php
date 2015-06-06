<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */
namespace skeeks\cms\widgets\formInputs\comboText;

use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
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
 * Class ComboTextInputWidget
 * @package skeeks\cms\widgets\formInputs\comboText
 */
class ComboTextInputWidget extends InputWidget
{
    /**
     * @var array Общие js опции
     */
    public $clientOptions = [];

    /**
     * @var array Опции для CKEditor
     */
    public $ckeditorOptions = [];
    public $codemirrorOptions = [];

    public $options = ['class' => 'form-control'];


    /**
     * @var \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor
     */
    public $ckeditor = null;

    /**
     * @var CodemirrorWidget
     */
    public $codemirror = null;


    public function init()
    {
        parent::init();

        if (!array_key_exists('id', $this->clientOptions))
        {
            $this->clientOptions['id'] = $this->id;
        }
    }
    /**
	 * @inheritdoc
	 */
	public function run()
	{
        if ($this->hasModel())
        {
            if (!array_key_exists('id', $this->options))
            {
                $this->clientOptions['inputId'] = Html::getInputId($model, $attribute);
            } else
            {
                $this->clientOptions['inputId'] = $this->options['id'];
            }

			$textarea = Html::activeTextarea($this->model, $this->attribute, $this->options);
		} else
        {
            //TODO: реализовать для работы без модели
            echo Html::textarea($this->name, $this->value, $this->options);
            return;
		}

        $this->registerPlugin();

        echo $this->render('combo-text', [
            'widget'    => $this,
            'textarea'  => $textarea
        ]);
	}



    /**
	 * Registers CKEditor plugin
	 */
	protected function registerPlugin()
	{
		$view = $this->getView();

        $this->ckeditor = new \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor(ArrayHelper::merge([
            'model'         => $this->model,
            'attribute'     => $this->attribute,
        ], $this->ckeditorOptions));

        $this->codemirror = new CodemirrorWidget(ArrayHelper::merge([
            'model'         => $this->model,
            'attribute'     => $this->attribute,
        ], $this->codemirrorOptions));

        $this->ckeditor->registerAssets();
        $this->codemirror->registerAssets();

        $this->clientOptions['ckeditor'] = $this->ckeditor->clientOptions;
        $this->clientOptions['codemirror'] = $this->codemirror->clientOptions;

	}
}

