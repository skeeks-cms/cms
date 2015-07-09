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
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\ComponentModel;
use skeeks\cms\models\StorageFile;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

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
     * Может ли быть файл привязан в эту группу.
     *
     * @param StorageFile $file
     * @return bool
     * @throws Exception
     */
    public function canBeAttachedFile(StorageFile $file)
    {
        //Если файл не привязан к этой сущьности то и в группу добавить его нельзя
        if (!$file->isLinkedToModel($this->owner))
        {
            throw new Exception('Файл не привязан к текущей моделе, поэтому не может быть добавлен в эту группу.');
        }

        //Если в конфиге указаны допустимые расширения файлов то проверим расширение файла
        if ($allowedExtensions = $this->getConfigAllowedExtensions())
        {
            if (!in_array(StringHelper::strtolower($file->extension), $allowedExtensions))
            {
                throw new Exception("Файл с расширением {$file->extension} не может быть привязан. Допустимые расширения: " . implode(", ", $allowedExtensions));
            }
        }

        //Если в конфиге указан максимальный размер файла
        if ($maxSize = $this->getConfigMaxSize())
        {
            if ((int) $file->size > (int) $maxSize)
            {
                $sizeFormated       = \Yii::$app->formatter->asSize($file->size);
                $maxSizeFormated    = \Yii::$app->formatter->asSize($maxSize);
                throw new Exception("Файл с размером {$sizeFormated} не может быть привязан. Максимальный размер файла: " . $maxSizeFormated);
            }
        }

        //Максимальное количество файлов
        if ($maxCountFiles = $this->getConfigMaxCountFiles())
        {
            if ($attachedFiles = count($this->fetchFiles()) >= $maxCountFiles)
            {
                throw new Exception("Максимально к этой группе можно привязать {$maxCountFiles}, уже привязано {$attachedFiles}");
            }
        }

        return true;
    }

    /**
     * Допустимые расширения файлов
     *
     * @return array
     */
    public function getConfigAllowedExtensions()
    {
        return (array) ArrayHelper::getValue($this->config, HasFiles::ALLOWED_EXTENSIONS, []);
    }

    /**
     * Максимальный размер файла
     *
     * @return int
     */
    public function getConfigMaxSize()
    {
        return (int) ArrayHelper::getValue($this->config, HasFiles::MAX_SIZE, 0);
    }

    /**
     * Проверка максимального количества файлов
     *
     * @return int
     */
    public function getConfigMaxCountFiles()
    {
        return (int) ArrayHelper::getValue($this->config, HasFiles::MAX_COUNT_FILES, 0);
    }

    /**
     * Привязка одного файла
     *
     * @param StorageFile $file
     * @return $this
     */
    public function setFile(StorageFile $file)
    {
        $this->items    = [];
        $this->items[]  = $file->src;

        $this->behavior->setInstanceFilesFromGroup();
        return $this;
    }
    /**
     *
     * Можно добавляь файл в группу, только если файл уже привязан к моделе
     *
     * @param StorageFile $file
     * @return $this
     */
    public function attachFile(StorageFile $file)
    {
        //Если в группе может быть только 1 файл, то делаем просто его установку убрав другой файл.
        if ($this->getConfigMaxCountFiles() == 1)
        {
            return $this->setFile($file);
        }

        $this->canBeAttachedFile($file);

        if ($files = $this->fetchFiles())
        {
            $this->items    = [];
            foreach ($files as $fileAttached)
            {
                $this->items[]  = $fileAttached->src;
            }
        }
        $this->items[]  = $file->src;
        $this->items    = array_unique($this->items);

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

       /* if ($files = $this->fetchFiles())
        {
            $this->items    = [];
            foreach ($files as $fileAttached)
            {
                $this->items[]  = $fileAttached->src;
            }
        }*/

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
     *
     * Содержит ли эта группа файл?
     *
     * @param $file
     * @return bool
     */
    public function hasFile($file)
    {
        $result = [];

        if ($file instanceof StorageFile)
        {
            $src = $file->src;
        } else
        {
            $src = $file;
        }

        return (bool) in_array($src, $this->items);
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

    /**
     * @return string
     */
    public function getFirstSrc()
    {
        $array = $this->items;
        return (string) array_shift($array);
    }
}