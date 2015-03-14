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
     * @var array
     */
    public $PjaxOptions = [];

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        $this->options = ArrayHelper::merge($this->options, [
            'class' => 'sx-form-admin'
        ]);

        if ($this->usePjax)
        {
            Pjax::begin(ArrayHelper::merge([
                'id' => 'sx-pjax-form-' . $this->id,
            ], $this->PjaxOptions));

            $this->options = ArrayHelper::merge($this->options, [
                'data-pjax' => true
            ]);

            echo \skeeks\cms\modules\admin\widgets\Alert::widget();
        }

        parent::init();
    }

    public function run()
    {
        /*$js = <<<JS
        // get the form id and set the event
        $('#{$this->id}').on('beforeSubmit', function(e) {
           alert('111');
        }).on('submit', function(e){
            alert('222');
        });
JS;
        $this->view->registerJs($js);*/

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
}