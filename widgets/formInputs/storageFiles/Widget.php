<?php
/**
 * StorageFileManager
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 22.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\formInputs\storageFiles;

use skeeks\cms\Exception;
use skeeks\cms\models\behaviors\HasFiles;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class Widget
 * @package skeeks\cms\widgets\formInputs\storageFiles
 */
class Widget extends InputWidget
{
    /**
     * @var array the options for the Bootstrap File Input plugin. Default options have exporting enabled.
     * Please refer to the Bootstrap File Input plugin Web page for possible options.
     * @see http://plugins.krajee.com/file-input#options
     */
    public $clientOptions = [];


    protected $_modelAttributeConfig = [];
    /**
     * @var HasFiles
     */
    protected $_behaviorFiles = null;

    /**
     * Берем поведения модели
     *
     */
    private function _initAndValidate()
    {
        if (!$this->hasModel())
        {
            throw new Exception("Этот файл рассчитан только для форм с моделью");
        }


        if (!$this->model->toArray())
        {
            throw new Exception("Файлы можно будет сохранять только после сохранения сущьности");
        }

        foreach ($this->model->getBehaviors() as $behavior)
        {
            if ($behavior instanceof HasFiles)
            {
                $behaviorHasFiles = $behavior;

                if ($behaviorHasFiles->hasField($this->attribute))
                {
                    $this->_modelAttributeConfig    = $behaviorHasFiles->getFieldConfig($this->attribute);
                    $this->_behaviorFiles           = $behaviorHasFiles;

                    return true;
                }
            }
        }

        throw new Exception("У текущей сущьности не задано поведение HasFiles");
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            $this->_initAndValidate();



            $valueArray = (array) Html::getAttributeValue($this->model, $this->attribute);
            $files = [];
            foreach ($valueArray as $src)
            {
                $backendDeleteUrl = Yii::$app->urlManager->createUrl(["cms/storage-files/detach-file",
                    "linked_to_model"   => $this->model->getRef()->getCode(),
                    "linked_to_value"   => $this->model->getRef()->getValue(),
                    "field"             => $this->attribute,
                    "src"               => $src
                ]);

                $one = Html::a(
                        Html::img($src, ["width" => "100", "class", "sx-real-src"]),
                            $src, ["target" => "_blank"]);

                $controlls = Html::a("удалить", $backendDeleteUrl, ["class" => "btn btn-danger btn-xs sx-file-controll-delete"]);
                $one .= Html::tag("div", $controlls, ["class" => "sx-file-controlls"]);

                $files[] = $one;
            }
            //Контейнер с файлами
            $result[] = Html::ul($files, [
                "class" => "sx-files",
                "encode" => false
            ]);
            $result[] = $this->_getSourcesString();
            //Прогресс бар
            $result[] = Html::tag("div", "", ["class" => "sx-progress-bar", "style" => "margin-top:10px;margin-bottom:10px;"]);

            $id  = "sx-id-" . Yii::$app->security->generateRandomString(6);
            $result = Html::tag("div", implode($result), ["id" => $id, "class" => "sx-file-manager"]);
            echo $result;

            $optionsForJs =
            [
                "id"            => $id,
                "SimpleUpload"  => $this->_getSourceSimpleUploadOptions()
            ];

            $optionsForJs = Json::encode($optionsForJs);
            \skeeks\widget\simpleajaxuploader\Asset::register($this->getView());

            $this->getView()->registerJs(<<<JS
            (function(sx, $, _)
            {
                new sx.classes.widgets.StorageFileManager({$optionsForJs});

            })(sx, sx.$, sx._);
JS
);

            $this->registerClientScript();

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }

    }


    /**
     * @return int
     */
    public function getOptionMaxCountFiles()
    {
        $config = $this->_modelAttributeConfig;
        if (isset($config[HasFiles::MAX_COUNT_FILES]))
        {
            return (int) $config[HasFiles::MAX_COUNT_FILES];
        }

        return 0;
    }

    /**
     * @return string
     */
    private function _getSourcesString()
    {
        $options["class"] = "sx-sources";
        $valueArray = Html::getAttributeValue($this->model, $this->attribute);
        if ($this->getOptionMaxCountFiles() > 0 && $this->getOptionMaxCountFiles() <= count($valueArray))
        {
            $options["style"] = "display: none;";
        }

        $idSourceSimpleUpload = "sx-id-" . Yii::$app->security->generateRandomString(6);
        return Html::tag("div", '<button id="' . $idSourceSimpleUpload . '" class="btn btn-primary btn-large source-simpleUpload">Загрузить файлы</button>', $options);


    }
    private function _getSourceSimpleUploadOptions()
    {
        $backendSimpleUpload = Yii::$app->urlManager->createUrl(["cms/storage-files/upload",
            "linked_to_model"   => $this->model->getRef()->getCode(),
            "linked_to_value"   => $this->model->getRef()->getValue(),
            "field"             => $this->attribute
        ]);


        //Опции которые перетирать нельзя
        $mainOptions =
        [
            "url"               => $backendSimpleUpload,
            "name"              => "imgfile", //TODO: хардкод
            "hoverClass"        => 'btn-hover',
            "focusClass"        => 'active',
            "disabledClass"     => 'disabled',
            "responseType"      => 'json',
            "multiplie"          => false,

        ];

        //Опции которые вычисляются из поведения моедли
        $fromBehaviorOptions = [];
        $config = $this->_modelAttributeConfig;
        if (isset($config[HasFiles::MAX_SIZE]))
        {
            $fromBehaviorOptions["maxSize"] = $config[HasFiles::MAX_SIZE];
        }

        if (isset($config[HasFiles::ALLOWED_EXTENSIONS]))
        {
            $fromBehaviorOptions["allowedExtensions"] = $config[HasFiles::ALLOWED_EXTENSIONS];
        }

        if (isset($config[HasFiles::ACCEPT_MIME_TYPE]))
        {
            $fromBehaviorOptions["accept"] = $config[HasFiles::ACCEPT_MIME_TYPE];
        }


        return array_merge($this->clientOptions, $fromBehaviorOptions, $mainOptions);
    }

    /**
     * Registers Bootstrap File Input plugin
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        Asset::register($view);
    }
}
