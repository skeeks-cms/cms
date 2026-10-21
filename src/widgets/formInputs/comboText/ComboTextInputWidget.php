<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.06.2015
 */

namespace skeeks\cms\widgets\formInputs\comboText;

use skeeks\cms\behaviors\RichTextBehavior;
use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\widgets\formInputs\richText\RichTextInputWidget;
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
    const CONTROLL_TEXT = "text";
    const CONTROLL_EDITOR = "editor";
    const CONTROLL_HTML = "html";

    public static function editors()
    {
        return [
            self::CONTROLL_TEXT => \Yii::t('skeeks/cms', 'Text'),
            self::CONTROLL_EDITOR => \Yii::t('skeeks/cms', 'Visual Editor'),
            self::CONTROLL_HTML => 'HTML',
        ];
    }

    public $defaultEditor = "text";

    /**
     * @var array Опции текстового поля по умолчанию.
     */
    public $defaultOptions = [
        'class' => 'form-control',
        'rows' => '20',
    ];

    /**
     * @var array Общие js опции текущего виджета
     */
    public $clientOptions = [];

    /**
     * @var string название поля, в котором будет храниться выбранный тип редактора.
     * Если не будет указан, то редактор по умолчанию будет выбран из настроек.
     */
    public $modelAttributeSaveType = "";


    /**
     * @var array Опции RichTextInputWidget режима «Визуальный редактор».
     * Текст считается доверенным HTML сайта; текст со скриптами, стилями и
     * встроенными блоками визуальный редактор не открывает — он правится в режиме HTML.
     * Картинки вставляются, только если у модели есть RichTextBehavior для этого поля
     * (он переносит их в хранилище при сохранении).
     */
    public $richTextOptions = [];

    /**
     * @var array Опции для CodeMirror
     */
    public $codemirrorOptions = [];

    /**
     * @var CodemirrorWidget
     */
    public $codemirror = null;


    public function init()
    {
        parent::init();

        if (!array_key_exists('id', $this->clientOptions)) {
            $this->clientOptions['id'] = $this->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->options = ArrayHelper::merge($this->defaultOptions, $this->options);

        if ($this->hasModel()) {
            if (!array_key_exists('id', $this->options)) {
                $this->clientOptions['inputId'] = Html::getInputId($this->model, $this->attribute);
            } else {
                $this->clientOptions['inputId'] = $this->options['id'];
            }

            $textarea = Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            //TODO: реализовать для работы без модели
            echo Html::textarea($this->name, $this->value, $this->options);
            return;
        }

        $this->registerPlugin();

        // The visual mode edits the same textarea; shown by combo-widget.js in the "editor" mode.
        $textarea .= RichTextInputWidget::widget(ArrayHelper::merge([
            'model'          => $this->model,
            'attribute'      => $this->attribute,
            'inputId'        => $this->clientOptions['inputId'],
            'preset'         => 'full',
            'trusted'        => true,
            'minHeight'      => 320,
            // Without the behavior nothing would move pasted pictures out of the temporary area.
            'uploadUrl'      => RichTextBehavior::covers($this->model, $this->attribute) ? ['/cms/admin-rich-text/upload'] : null,
            'wrapperOptions' => ['hidden' => true, 'data-sx-combo-richtext' => true],
        ], $this->richTextOptions));

        echo $this->render('combo-text', [
            'widget' => $this,
            'textarea' => $textarea
        ]);
    }


    /**
     * Registers CodeMirror and the widget script
     */
    protected function registerPlugin()
    {
        $this->codemirror = new CodemirrorWidget(ArrayHelper::merge([
            'model' => $this->model,
            'attribute' => $this->attribute,

            'preset' => 'htmlmixed',
            'assets' =>
                [
                    \skeeks\widget\codemirror\CodemirrorAsset::THEME_NIGHT
                ],
            'clientOptions' =>
                [
                    'theme' => 'night'
                ],

        ], $this->codemirrorOptions));

        $this->codemirror->registerAssets();

        $this->clientOptions['codemirror'] = $this->codemirror->clientOptions;

        ComboTextInputWidgetAsset::register($this->view);

    }
}

