<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.04.2015
 */

namespace skeeks\cms\widgets;

use skeeks\cms\admin\assets\ActionFilesAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @property array $defaultClientOptions read-only
 *
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 * @deprecated
 */
class StorageFileManager extends Widget
{
    const SOURCE_SIMPLE_UPLOAD = 'simpleUpload';
    const SOURCE_REMOTE_UPLOAD = 'remoteUpload';

    public $model;

    /** @var array $targets */
    public $clientOptions = [];

    public $backendSimpleUploadUrl = '';
    public $backendRemoteUploadUrl = '';


    public $simpleUploadButtons = '';
    public $remoteUploadButtonSelector = '';

    public function init()
    {
        parent::init();

        if (!$this->backendSimpleUploadUrl) {
            $this->backendSimpleUploadUrl = Url::to(['/cms/admin-storage-files/upload']);
        }
        if (!$this->backendRemoteUploadUrl) {
            $this->backendRemoteUploadUrl = Url::to(['/cms/admin-storage-files/remote-upload']);
        }

        $clientOptions = ArrayHelper::merge($this->defaultClientOptions, $this->clientOptions);

        $options = [
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
        ActionFilesAsset::register($this->getView());

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
        $str_upload = \Yii::t('skeeks/cms', 'Upload');
        $str_togg_drop = \Yii::t('skeeks/cms', 'Toggle Dropdow');
        $str_upl_from_comp = \Yii::t('skeeks/cms', 'Upload from your computer');
        $str_remote_upl = \Yii::t('skeeks/cms', 'Upload by link {http}', ['http' => 'http://']);
        $str_up_f = \Yii::t('skeeks/cms', 'The upload file');
        $str_up_fs = \Yii::t('skeeks/cms', 'The upload files');
        return <<<HTML
    <div id="sx-file-manager-{$this->id}">

        <div class="sx-upload-sources">

            <div class="btn-group">
              <button type="button" id="source-simpleUpload-{$this->id}" class="btn btn-default btn-secondary source-simpleUpload">
                  <i class="fas fa-download"></i> {$str_upload}
              </button>
              <button type="button" class="btn btn-default btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
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
            <div class="sx-progress-bar-wrapper">
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

        <div class="sx-progress-bar-{$this->id}" style="display: none;">
            <span style="vertical-align:middle;">{$str_up_fs} (<span class="sx-uploadedFiles"></span> / <span class="sx-allFiles"></span>)</span>
            <div class="sx-progress-bar-wrapper">
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

    </div>
HTML;
    }

    public function getDefaultClientOptions()
    {
        $result['simpleUpload']['options'] = $this->_getSourceSimpleUploadOptions();
        $result['remoteUpload'] = $this->_getSourceRemoteUploadOptions();

        return $result;
    }

    protected function _getSourceSimpleUploadOptions()
    {
        //Опции которые перетирать нельзя
        $options =
            [
                "url" => $this->backendSimpleUploadUrl,
                "name" => "imgfile", //TODO: хардкод
                "hoverClass" => 'btn-hover',
                "focusClass" => 'active',
                "disabledClass" => 'disabled',
                "responseType" => 'json',
                "multiple" => true,
            ];

        return $options;
    }

    protected function _getSourceRemoteUploadOptions()
    {
        $options =
            [
                "url" => $this->backendRemoteUploadUrl,
                "name" => "imgfile", //TODO: хардкод
                "hoverClass" => 'btn-hover',
                "focusClass" => 'active',
                "disabledClass" => 'disabled',
                "responseType" => 'json',
                "multiple" => true,
            ];

        return $options;
    }

}