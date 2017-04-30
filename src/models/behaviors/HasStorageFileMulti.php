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
use skeeks\cms\models\StorageFile;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

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
    public $relations = [
        [
            'relation' => 'images',
            'property' => 'imageIds'
        ]
    ];

    /**
     * При удалении сущьности удалять все привязанные файлы?
     *
     * @var string
     */
    public $onDeleteCascade = true;

    /**
     * @var array
     */
    protected $_removeFiles = [];
    protected $_linkFiles = [];

    
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE      => "deleteStorgaFile",

            BaseActiveRecord::EVENT_BEFORE_INSERT        => [$this, "saveStorgaFile"],
            BaseActiveRecord::EVENT_BEFORE_UPDATE       => "saveStorgaFile",

            BaseActiveRecord::EVENT_AFTER_INSERT      => "afterSaveStorgaFile",
            BaseActiveRecord::EVENT_AFTER_UPDATE      => "afterSaveStorgaFile",
        ];
    }
    
    /**
     * Загрузка файлов в хранилище и их сохранение со связанной сущьностью
     *
     * @param $e
     */
    public function saveStorgaFile($e)
    {
        foreach ($this->relations as $data)
        {
            $fieldCode = ArrayHelper::getValue($data, 'property');
            $relation = ArrayHelper::getValue($data, 'relation');

            $oldFiles = $this->owner->{$relation};
            $oldIds = [];

            if ($oldFiles)
            {
                $oldIds = ArrayHelper::map($oldFiles, 'id', 'id');
            }

            $files = [];
            if ($this->owner->{$fieldCode} && is_array($this->owner->{$fieldCode}))
            {
                foreach ($this->owner->{$fieldCode} as $fileId)
                {
                    if (is_string($fileId) && ((string) (int) $fileId != (string) $fileId))
                    {
                        try
                        {
                            $file = \Yii::$app->storage->upload($fileId);
                            if ($file)
                            {
                                if ($this->owner->isNewRecord)
                                {
                                    $this->_linkFiles[$relation][] = $file;
                                } else
                                {
                                    $this->owner->link($relation, $file);
                                }
                                $files[] = $file->id;
                            }

                        } catch (\Exception $e)
                        {}
                    } else
                    {
                        $files[] = $fileId;
                        ArrayHelper::remove($oldIds, $fileId);
                    }
                }

                $this->owner->{$fieldCode} = $files;
            }

            /**
             * Удалить старые файлы
             */
            if ($oldIds)
            {
                $this->_removeFiles = $oldIds;
            }


        }
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function afterSaveStorgaFile()
    {
        if ($this->_linkFiles)
        {
            foreach ($this->_linkFiles as $relation => $files)
            {
                foreach ($files as $file)
                {
                    $this->owner->link($relation, $file);
                }

            }
        }

        if ($this->_removeFiles)
        {
            if ($files = StorageFile::find()->where(['id' => $this->_removeFiles])->all())
            {
                foreach ($files as $file)
                {
                    $file->delete();
                }
            }
        }
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


        foreach ($this->relations as $data)
        {
            $fieldName = ArrayHelper::getValue($data, 'property');

            if ($fileIds = $this->owner->{$fieldName})
            {
                if ($storageFiles = CmsStorageFile::find()->where(['id' => $fileIds])->all())
                {
                    foreach ($storageFiles as $file)
                    {
                        $file->delete();
                    }

                }
            }
        }
    }
}