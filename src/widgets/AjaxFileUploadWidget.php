<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 04.05.2017
 */
namespace skeeks\cms\widgets;
use skeeks\cms\models\CmsStorageFile;

/**
 * @property CmsStorageFile $cmsFile
 *
 * Class AjaxFileUploadWidget
 * @package skeeks\cms\widgets
 */
class AjaxFileUploadWidget extends \skeeks\yii2\ajaxfileupload\widgets\AjaxFileUploadWidget
{
    protected function _initClientFiles()
    {
        if ($this->multiple)
        {
            if ($this->cmsFiles)
            {
                foreach ($this->cmsFiles as $file)
                {
                    $fileData = [
                        'name' => $file->fileName,
                        'value' => $file->id,
                        'state' => 'success',
                        'size' => $file->size,
                        'type' => $file->mime_type,
                        'src' => $file->src,
                    ];

                    if ($file->isImage())
                    {
                        $fileData['image'] = [
                            'height' => $file->image_height,
                            'width' => $file->image_width,
                        ];
                        $fileData['preview'] = Html::img($file->src);
                    }

                    $this->clientOptions['files'][] = $fileData;
                }
            }

        } else
        {
            if ($this->cmsFile)
            {
                $fileData = [
                    'name' => $this->cmsFile->fileName,
                    'value' => $this->cmsFile->id,
                    'state' => 'success',
                    'size' => $this->cmsFile->size,
                    'type' => $this->cmsFile->mime_type,
                    'src' => $this->cmsFile->src,
                ];
                if ($this->cmsFile->isImage())
                {
                    $fileData['image'] = [
                        'height' => $this->cmsFile->image_height,
                        'width' => $this->cmsFile->image_width,
                    ];

                    $fileData['preview'] = Html::img($this->cmsFile->src);
                }
                $this->clientOptions['files'][] = $fileData;
            }

        }

        return $this;
    }

    /**
     * @return null|CmsStorageFile
     */
    public function getCmsFile()
    {
        if ($fileId = $this->model->{$this->attribute})
        {
            return CmsStorageFile::findOne((int) $fileId);
        }

        return null;
    }

    /**
     * @return null|CmsStorageFile[]
     */
    public function getCmsFiles()
    {
        if ($fileId = $this->model->{$this->attribute})
        {
            return CmsStorageFile::find()->where(['id' => $fileId])->all();
        }

        return null;
    }
}