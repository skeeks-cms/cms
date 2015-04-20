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
class StorageFileManager extends Widget
{
    public $model;
    public $fileGroup;

    /** @var array $targets */
    public $clientOptions = [];

    public $simpleUploadButtons = '';
    public $remoteUploadButtonSelector = '';

    public function init()
    {
        parent::init();

        $clientOptions = ArrayHelper::merge($this->defaultClientOptions(), $this->clientOptions);

        $options = [
            'commonData' => [
                'group' => $this->fileGroup
            ],

            'simpleUploadButtons' => [
                'source-simpleUpload-' . $this->id,
                'source-simpleUpload-2-' . $this->id,
            ],
            'remoteUploadButtonSelector' => '.source-remoteUpload-' . $this->id,
            'allUploadProgressSelector' => '.sx-progress-bar-' . $this->id,
            'oneFileUploadProgressSelector' => '.sx-progress-bar-file-' . $this->id,
        ];


        $clientOptions = ArrayHelper::merge($clientOptions, $options);

        $clientOptionsString = \yii\helpers\Json::encode($clientOptions);
        \skeeks\cms\modules\admin\assets\ActionFilesAsset::register($this->getView());

        $fileManagerId = "sx-file-manager-{$this->id}";

        $this->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            new sx.classes.CustomFileManager('#$fileManagerId', {$clientOptionsString});

        })(sx, sx.$, sx._);
JS
);

    }


    public function run()
    {

    return <<<HTML
    <div id="sx-file-manager-{$this->id}">

        <div class="sx-upload-sources">

            <div class="btn-group">
              <button type="button" id="source-simpleUpload-{$this->id}" class="btn btn-default source-simpleUpload">
                  <i class="glyphicon glyphicon-download-alt"></i> Загрузить
              </button>
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" id="source-simpleUpload-2-{$this->id}" ><i class="glyphicon glyphicon-download-alt"></i> Загрузить с компьютера</a></li>
                <li><a href="#" id="source-remoteUpload-{$this->id}" class="source-remoteUpload-{$this->id}" ><i class="glyphicon glyphicon-globe "></i> Загрузить по ссылке http://</a></li>
              </ul>
            </div>
        </div>

        <div class="sx-progress-bar-file-{$this->id}" style="display: none;">
            <span style="vertical-align:middle;">Загрузка файла: <span class="sx-uploaded-file-name"></span></span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

        <div class="sx-progress-bar-{$this->id}" style="display: none;">
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

        ];

        if ($this->model)
        {
            $urlData = ArrayHelper::merge($urlData, [
                "linked_to_model"   => $this->model->getRef()->getCode(),
                "linked_to_value"   => $this->model->getRef()->getValue(),
            ]);
        }

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

        $result['remoteUpload'] = $this->_getSourceRemoteUploadOptions();

        return $result;
    }

    private function _getSourceRemoteUploadOptions()
    {
        $urlData = [
            "cms/storage-files/remote-upload",
        ];

        if ($this->model)
        {
            $urlData = ArrayHelper::merge($urlData, [
                "linked_to_model"   => $this->model->getRef()->getCode(),
                "linked_to_value"   => $this->model->getRef()->getValue(),
            ]);
        }


        if ($this->fileGroup)
        {
            $urlData["group"] = $this->fileGroup;
        }
        $backendRemoteUpload = \Yii::$app->urlManager->createUrl($urlData);
        $mainOptions =
            [
                "url"               => $backendRemoteUpload,
                "name"              => "imgfile", //TODO: хардкод
                "hoverClass"        => 'btn-hover',
                "focusClass"        => 'active',
                "disabledClass"     => 'disabled',
                "responseType"      => 'json',
                "multiplie"          => true,

            ];

        $fromBehaviorOptions = [];
        return array_merge($fromBehaviorOptions, $mainOptions);
    }

}