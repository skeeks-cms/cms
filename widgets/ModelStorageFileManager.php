<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.04.2015
 */

namespace skeeks\cms\widgets;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 */
class ModelStorageFileManager extends Widget
{
    public $model;
    public $fileGroup;

    /** @var array $targets */
    public $clientOptions = [];

    public $simpleUploadBtnTitle = "Добавить файлы";

    public function init()
    {
        parent::init();


        $clientOptions = ArrayHelper::merge($this->defaultClientOptions(), $this->clientOptions);

        $clientOptionsString = \yii\helpers\Json::encode($clientOptions);
        \skeeks\cms\modules\admin\assets\ActionFilesAsset::register($this->getView());

        $this->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.FileManager = new sx.classes.DefaultFileManager('#sx-file-manager', {$clientOptionsString});
        })(sx, sx.$, sx._);
JS
);

    }


    public function run()
    {

        $simpleUploadBtnTitle = $this->simpleUploadBtnTitle;
    return <<<HTML
    <div id="sx-file-manager">

        <div class="sx-upload-sources">

            <a href="#" id="source-simpleUpload" class="btn btn-primary btn-sm source-simpleUpload">
                <i class="glyphicon glyphicon-paperclip"></i>
                {$simpleUploadBtnTitle}
            </a>

        </div>

        <div class="sx-progress-bar-file" style="display: none;">
            <span style="vertical-align:middle;">Загрузка файла: <span class="sx-uploaded-file-name"></span></span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

        <div class="sx-progress-bar" style="display: none;">
            <span style="vertical-align:middle;">Загрузка файлов (<span class="sx-uploadedFiles"></span> / <span class="sx-allFiles"></span>)</span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

    </div>
HTML;
    }

    public function defaultClientOptions($group = '')
    {
        $urlData = [
            "cms/storage-files/upload",
            "linked_to_model"   => $this->model->getRef()->getCode(),
            "linked_to_value"   => $this->model->getRef()->getValue(),
        ];

        if ($this->fileGroup)
        {
            $urlData["group"] = $this->fileGroup;
        }

        $backendSimpleUpload = \Yii::$app->urlManager->createUrl($urlData);

        //Опции которые перетирать нельзя
        $mainOptions =
        [
            "url"               => $backendSimpleUpload,
            "name"              => "imgfile", //TODO: хардкод
            "hoverClass"        => 'btn-hover',
            "focusClass"        => 'active',
            "disabledClass"     => 'disabled',
            "responseType"      => 'json',
            "multiplie"          => true,

        ];

        $result['simpleUpload']['options'] = $mainOptions;

        return $result;
    }
}