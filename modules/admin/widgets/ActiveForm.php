<?php
/**
 * ActiveForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\validators\db\IsNewRecord;
use skeeks\sx\validate\Validate;
use skeeks\widget\chosen\Chosen;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\Pjax;

/**
 * Class ActiveForm
 * @package skeeks\cms\modules\admin\widgets
 */
class ActiveForm extends \skeeks\cms\base\widgets\ActiveForm
{
    /**
     * @var bool
     */
    public $usePjax = true;

    /**
     * @var bool
     */
    public $enableAjaxValidation = true;
    /**
     * TODO:: Is depricated
     * @var array
     */
    public $PjaxOptions = [];

    /**
     * @var array
     */
    public $pjaxOptions = [];

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        $this->pjaxOptions = ArrayHelper::merge($this->pjaxOptions, $this->PjaxOptions);

        if ($classes = ArrayHelper::getValue($this->options, 'class'))
        {
            $this->options = ArrayHelper::merge($this->options, [
                'class' => $classes . ' sx-form-admin'
            ]);
        } else
        {
            $this->options = ArrayHelper::merge($this->options, [
                'class' => 'sx-form-admin'
            ]);
        }



        if ($this->usePjax)
        {
            Pjax::begin(ArrayHelper::merge([
                'id' => 'sx-pjax-form-' . $this->id,
            ], $this->pjaxOptions));

            $this->options = ArrayHelper::merge($this->options, [
                'data-pjax' => true
            ]);

            echo \skeeks\cms\modules\admin\widgets\Alert::widget();
        }

        parent::init();
    }

    public function run()
    {
        parent::run();

        if ($this->usePjax)
        {
            Pjax::end();
        }
    }


    /**
     * @param Model $model
     * @return string
     */
    public function buttonsCreateOrUpdate(Model $model)
    {
        if (Validate::validate(new IsNewRecord(), $model)->isValid())
        {
            $submit = Html::submitButton("<i class=\"glyphicon glyphicon-saved\"></i> " . \Yii::t('app', 'Create'), ['class' => 'btn btn-success']);
        } else
        {
            $submit = Html::submitButton("<i class=\"glyphicon glyphicon-saved\"></i> " .  \Yii::t('app', 'Update'), ['class' => 'btn btn-primary']);
        }
        return Html::tag('div',
            $submit,
            ['class' => 'form-group']
        );
    }

    public function fieldSet($name, $options = [])
    {
        return <<<HTML
        <div class="sx-form-fieldset">
            <h3 class="sx-form-fieldset-title">{$name}</h3>
            <div class="sx-form-fieldset-content">
HTML;

    }

    public function fieldSetEnd()
    {
        return <<<HTML
            </div>
        </div>
HTML;

    }


    /**
     *
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
                'items'         => $items,
            ]
        );

        foreach ($config as $key => $value)
        {
            if (property_exists(Chosen::className(), $key) === false)
            {
                unset($config[$key]);
            }
        }

        return $this->field($model, $attribute, $fieldOptions)->widget(
            Chosen::className(),
            $config
        );
    }
}