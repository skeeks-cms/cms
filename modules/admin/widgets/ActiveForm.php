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
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\assets\AdminFormAsset;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\ActiveFormTrait;
use skeeks\cms\modules\admin\traits\AdminActiveFormTrait;
use skeeks\cms\traits\ActiveFormAjaxSubmitTrait;
use skeeks\sx\validate\Validate;
use skeeks\widget\chosen\Chosen;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\Pjax;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Class ActiveForm
 * @package skeeks\cms\modules\admin\widgets
 */
class ActiveForm extends \skeeks\cms\base\widgets\ActiveForm
{
    use AdminActiveFormTrait;
    use ActiveFormAjaxSubmitTrait;

    /**
     * @var bool
     */
    public $usePjax = true;

    /**
     * @var bool
     */
    public $useAjaxSubmit = false;
    public $afterValidateCallback = "";

    /**
     * @var bool
     */
    public $enableAjaxValidation = true;

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

        AdminFormAsset::register($this->view);

        if ($this->useAjaxSubmit)
        {
            $this->registerJs();
        }

        if ($this->usePjax)
        {
            Pjax::end();
        }
    }


    /**
     * TODO: is depricated (1.2) use buttonsStandart
     * @param Model $model
     * @return string
     */
    public function buttonsCreateOrUpdate(Model $model)
    {
        /*if (Validate::validate(new IsNewRecord(), $model)->isValid())
        {
            $submit = Html::submitButton("<i class=\"glyphicon glyphicon-saved\"></i> " . \Yii::t('app', 'Create'), ['class' => 'btn btn-success']);
        } else
        {
            $submit = Html::submitButton("<i class=\"glyphicon glyphicon-saved\"></i> " .  \Yii::t('app', 'Update'), ['class' => 'btn btn-primary']);
        }*/
        return $this->buttonsStandart($model);
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