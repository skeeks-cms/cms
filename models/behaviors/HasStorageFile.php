<?php
/**
 * Если к моделе привязаны файлы из хранилище, то при удалении модели будут удалены все свяазнные файлы из хранилища.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\models\CmsStorageFile;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * Class HasStorageFile
 * @package skeeks\cms\models\behaviors
 */
class HasStorageFile extends Behavior
{
    /**
     * Набор полей модели к которым будут привязываться id файлов
     * @var array
     */
    public $fields = ['image_id'];

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE      => "deleteStorgaFile",
        ];
    }

    /**
     * До удаления сущьности, текущей необходим проверить все описанные модели, и сделать операции с ними (удалить, убрать привязку или ничего не делать кинуть Exception)
     * @throws Exception
     */
    public function deleteStorgaFile()
    {
        foreach ($this->fields as $fieldValue)
        {
            if ($fileId = $this->owner->{$fieldValue})
            {
                if ($storageFile = CmsStorageFile::findOne($fileId))
                {
                    $storageFile->delete();
                }
            }
        }
    }
}