<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\gridView;

use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\models\CmsContent;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * @property CmsContent $cmsContent;
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class GridViewCmsWidget extends WidgetRenderable
{
    /**
     * @var
     */
    public $modelClassName;


    /**
     * @var array
     */
    public $grid = [];

    /**
     * @var array по умолчанию включенные колонки
     */
    public $defaultEnabledColumns = [];

    /**
     * @var array переданный конфиг колонок при вызове
     */
    public $columns = [];

    public $test = '1';
    public $submodel;

    public function init()
    {
        parent::init();

        $this->submodel = new DynamicModel(['test']);
        $this->submodel->addRule(['test'], 'integer');
    }

    public function rules()
    {
        return [
            ['test', 'string'],
            ['submodel', 'safe']
        ];
    }

    public function attributeLabels()
    {
        return [
            'test' => 'Тест',
            'test' => 'Тест'
        ];
    }

    public function getConfigFormFields()
    {
        return [
            'test' => [
                'class' => WidgetField::class,
                'widgetClass' => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept' => 'image/*',
                    'multiple' => false
                ]
            ]
        ];
    }

    /*public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->field($this, 'test');
        echo $activeForm->field($this, 'test');
    }*/


    /**
     * @var array автоматически созданные колонки
     */
    protected $_autoColumns = [];


    protected function _run()
    {
        echo '1';
    }

    /**
     * @return string
     */
    public function getGridClassName()
    {
        return (string)ArrayHelper::getValue($this->grid, 'class', GridViewWidget::class);
    }

    /**
     * @return []
     */
    public function getGridConfig()
    {
        $gridConfig = ArrayHelper::getValue($this->grid, 'class');
        ArrayHelper::remove($gridConfig, 'class');
        $gridConfig['columns'] = $this->columns;
        $gridConfig['dataProvider'] = $this->dataProvider;

        return (array)$gridConfig;
    }

    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        $models = $this->dataProvider->getModels();
        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                    $this->_autoColumns[] = (string) $name;
                }
            }
        }

        return $this;
    }

}