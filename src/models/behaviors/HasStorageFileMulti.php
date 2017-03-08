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
 * Class HasStorageFileMulti
 * @package skeeks\cms\models\behaviors
 */
class HasStorageFileMulti extends Behavior
{
    /**
     * Набор полей модели к которым будут привязываться id файлов
     * @var array
     */
    public $relations = ['images'];

    /**
     * При удалении сущьности удалять все привязанные файлы?
     *
     * @var string
     */
    public $onDeleteCascade = true;

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
        if (!$this->onDeleteCascade)
        {
            return $this;
        }

        foreach ($this->relations as $relationNmae)
        {
            if ($files = $this->owner->{$relationNmae})
            {
                foreach ($files as $file)
                {
                    $file->delete();
                }
            }
        }
    }
}