<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 05.05.2017
 */

namespace skeeks\cms\validators;

use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * Class FileValidator
 *
 * @package skeeks\yii2\ajaxfileupload\validators
 */
class FileValidator extends \yii\validators\FileValidator
{
    /**
     * @param mixed $value
     *
     * @return array|null
     */
    protected function validateValue($value)
    {
        if (is_string($value) && file_exists($value)) {
            $uploadFile = new UploadedFile();
            $uploadFile->size = filesize($value);
            $uploadFile->type = FileHelper::getMimeType($value, null, false);
            $uploadFile->tempName = $value;
            $uploadFile->name = $value;

            $value = $uploadFile;

            return parent::validateValue($value);
        } else {
            //TODO:: Создавать UploadedFile из CmsStorageFile
            return null;
        }
    }

    public function validateAttribute($model, $attribute)
    {
        if ($this->maxFiles != 1) {
            $files = $model->$attribute;
            if (!is_array($files)) {
                //$this->addError($model, $attribute, $this->uploadRequired);
                return;
            }
            if ($this->maxFiles && count($files) > $this->maxFiles) {
                $this->addError($model, $attribute, $this->tooMany, ['limit' => $this->maxFiles]);
            } else {
                foreach ($files as $file) {
                    $result = $this->validateValue($file);
                    if (!empty($result)) {
                        $this->addError($model, $attribute, $result[0], $result[1]);
                    }
                }
            }
        } else {
            return parent::validateAttribute($model, $attribute);
        }
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        return '';
    }
}