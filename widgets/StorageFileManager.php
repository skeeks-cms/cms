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
        $str_upload = \Yii::t('app','Upload');
        $str_togg_drop = \Yii::t('app','Toggle Dropdow');
        $str_upl_from_comp = \Yii::t('app','Upload from your computer');
        $str_remote_upl = \Yii::t('app','Upload by link {http}',['http' => 'http://']);
        $str_up_f = \Yii::t('app','The upload file');
        $str_up_fs = \Yii::t('app','The upload files');
    return <<<HTML
    <div id="sx-file-manager-{$this->id}">

        <div class="sx-upload-sources">

            <div class="btn-group">
              <button type="button" id="source-simpleUpload-{$this->id}" class="btn btn-default source-simpleUpload">
                  <i class="glyphicon glyphicon-download-alt"></i> {$str_upload}
              </button>
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">{$str_togg_drop}</span>
              </button>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" id="source-simpleUpload-2-{$this->id}" ><i class="glyphicon glyphicon-download-alt"></i> {$str_upl_from_comp}</a></li>
                <li><a href="#" id="source-remoteUpload-{$this->id}" class="source-remoteUpload-{$this->id}" ><i class="glyphicon glyphicon-globe "></i> {$str_remote_upl}</a></li>
              </ul>
            </div>
        </div>

        <div class="sx-progress-bar-file-{$this->id}" style="display: none;">
            <span style="vertical-align:middle;">{$str_up_f}: <span class="sx-uploaded-file-name"></span></span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

        <div class="sx-progress-bar-{$this->id}" style="display: none;">
            <span style="vertical-align:middle;">{$str_up_fs} (<span class="sx-uploadedFiles"></span> / <span class="sx-allFiles"></span>)</span>
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
            "multiple"          => true,

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
                "multiple"          => true,

            ];

        $fromBehaviorOptions = [];
        return array_merge($fromBehaviorOptions, $mainOptions);
    }

}