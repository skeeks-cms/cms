<?php
/**
 * ModelFilesGroup
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\helpers;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\ComponentModel;
use skeeks\cms\models\StorageFile;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class ModelFilesGroup
 * @package skeeks\cms\models\helpers
 */
class ModelFilesGroup extends ComponentModel
{
    /**
     * @var array
     */
    public $config = [];
    /**
     * @var array
     */
    public $items = [];

    /**
     * @var ActiveRecord
     */
    public $owner       = null;

    /**
     * @var HasFiles
     */
    public $behavior    = null;


    /**
     *
     * Можно добавляь файл в группу, только если файл уже привязан к моделе
     *
     * @param StorageFile $file
     * @return $this
     */
    public function attachFile(StorageFile $file)
    {
        if ($file->isLinkedToModel($this->owner))
        {
            $this->items[] = $file->src;
            $this->items = array_unique($this->items);
        }

        $this->behavior->setInstanceFilesFromGroup();
        return $this;
    }

    /**
     *
     * Отвязать файл от группы
     *
     * @param StorageFile|string $file
     * @return $this
     */
    public function detachFile($file)
    {
        $result = [];

        if ($file instanceof StorageFile)
        {
            $src = $file->src;
        } else
        {
            $src = $file;
        }

        if ($this->items)
        {
            foreach ($this->items as $itemSrc)
            {
                if ($src != $itemSrc)
                {
                    $result[] = $itemSrc;
                }
            }
        }

        $this->items = $result;
        $this->behavior->setInstanceFilesFromGroup();

        return $this;
    }


    /**
     * @param bool $validate
     * @return $this
     */
    public function save($validate = false)
    {
        $this->behavior->setInstanceFilesFromGroup();
        $this->owner->save($validate);
        return $this;
    }



    /**
     * @return ActiveQuery
     */
    public function findFiles()
    {
        return StorageFile::find()->where(['src' => (array) $this->items]);
    }

    /**
     * @return \skeeks\cms\models\StorageFile[]
     */
    public function fetchFiles()
    {
        return $this->findFiles()->all();
    }

    /**
     * @return \skeeks\cms\models\StorageFile[]
     */
    public function getFiles()
    {
        return $this->fetchFiles();
    }
}