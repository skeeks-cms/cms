<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */

namespace skeeks\cms\widgets\formInputs\ckeditor;

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\Exception;
use skeeks\yii2\ckeditor\CKEditorWidget;
use yii\helpers\ArrayHelper;

/**
 * Class Ckeditor
 * @package skeeks\cms\widgets\formInputs\ckeditor
 */
class Ckeditor extends CKEditorWidget
{
    /**
     * @var Модель к которой привязываются файлы
     */
    public $relatedModel;

    public function __construct($config = [])
    {
        if (\Yii::$app->admin->requestIsAdmin) {
            $config = ArrayHelper::merge(\Yii::$app->admin->getCkeditorOptions(), $config);
        }

        parent::__construct($config);
    }

    public function init()
    {
        $additionalData = [];
        /*if ($this->relatedModel && ($this->relatedModel instanceof ActiveRecord && !$this->relatedModel->isNewRecord)) {
            $additionalData = [
                'className' => $this->relatedModel->className(),
                'pk' => $this->relatedModel->primaryKey,
            ];
        }*/

        $url = BackendUrlHelper::createByParams(['/cms/admin-storage-files'])
            ->enableEmptyLayout()
            ->setCallbackEventName("ckeditor")
            ->url;

        //$this->clientOptions['filebrowserImageUploadUrl'] = $url;
        $this->clientOptions['filebrowserImageBrowseUrl'] = $url;
        $this->clientOptions['filebrowserBrowseUrl'] = $url;


        /*$this->clientOptions['filebrowserImageUploadUrl'] = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-tools/select-file'])
            ->merge($additionalData)
            ->enableEmptyLayout()
            ->url;

        $this->clientOptions['filebrowserImageBrowseUrl'] = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-tools/select-file'])
            ->merge($additionalData)
            ->enableEmptyLayout()
            ->url;

        $this->clientOptions['filebrowserBrowseUrl'] = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-tools/select-file'])
            ->merge($additionalData)
            ->enableEmptyLayout()
            ->url;*/

        parent::init();
    }
}
