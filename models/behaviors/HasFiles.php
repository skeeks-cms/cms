<?php
/**
 * HasFiles
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\components\storage\Exception;
use skeeks\cms\models\behaviors\events\AfterUnLinkedModel;
use skeeks\cms\models\helpers\ModelFilesGroups;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\Vote;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 *
 *
 *
 *             [
                "class"  => behaviors\HasFiles::className(),
                "fields" =>
                [
                    "images" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 2000,
                        behaviors\HasFiles::MAX_SIZE            => 200,
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 1,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "files" => [],
                    "image_cover" => [],
                    "image" => []
                ]
            ],
 *
 *
 * Class HasComments
 * @package common\models\behaviors
 */
class HasFiles extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\StorageFile'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные файлы";

    /**
     * Все возможные настройки
     */
    const MAX_SIZE_TOTAL        = "maxSizeTotal";
    const MAX_SIZE              = "maxSize";
    const ALLOWED_EXTENSIONS    = "allowedExtensions";
    const MAX_COUNT_FILES       = "maxCountFiles";
    const ACCEPT_MIME_TYPE      = "acceptMimeType";

    /**
     * Настройки групп файлов
     * @var array
     */
    public $groups = [];

    /**
     * Общий конфиг, будет добавляться к каждой группе
     * @var array
     */
    public $config = [];

    /**
     * Названия поля в базе данных где будут храниться файлы
     * @var string
     */
    public $filesFieldName = 'files';


    /**
     * Все группы будем хранить в json encode
     * @param \skeeks\cms\base\db\ActiveRecord $owner
     */
    public function attach($owner)
    {
        $owner->attachBehavior(HasJsonFieldsBehavior::className() . $this->className(), [
            "class"  => HasJsonFieldsBehavior::className(),
            "fields" => [$this->filesFieldName]
        ]);

        parent::attach($owner);
    }

    /**
     * При отсоединении файла от модели, нужно убрать этот файл из всех групп.
     * @return array
     */
    public function events()
    {
        return array_merge(parent::events(), [
            CanBeLinkedToModel::EVENT_AFTER_UN_LINKED       => "unLinkedModel",
        ]);
    }

    /**
     * Если отвязана сущьность голос, то пересчитываем количество голосов
     * @param AfterUnLinkedModel $event
     */
    public function unLinkedModel(AfterUnLinkedModel $event)
    {
        if ($event->model)
        {
            if ($event->model instanceof StorageFile)
            {
                foreach ($this->getFilesGroups()->getComponents() as $group)
                {
                    $group->detachFile($event->model->src)->save();
                }
            }
        }
    }


    /**
     * Запрос на поиск файлов привязанных к текущей сущьности
     * TODO: реализовать
     * @return \yii\db\ActiveQuery
     */
    public function findFiles()
    {
        $className = \Yii::$app->registeredModels->getClassNameByCode($this->owner->className());

        if (!$className)
        {
            return null;
        }

        return StorageFile::find()->where(['linked_to_model' => $className]);
    }

    /**
     * Получение всех файлов привязанных к сущности.
     * TODO: реализовать
     * @return StorageFile[]
     */
    public function getFiles()
    {
        if ($find = $this->findFiles())
        {
            return $find->all();
        }

        return [];
    }


    /**
     * Конфиг для загрузки файлов
     * @return array
     */
    public function getFilesConfig()
    {
        return (array) $this->config;
    }

    /**
     * @var ModelFilesGroups
     */
    protected $_groups = null;
    /**
     * @return ModelFilesGroups
     */
    public function getFilesGroups()
    {
        if ($this->_groups === null)
        {
            //if ($this->groups)
            //{
                $oiginalFilesData = $this->owner->{$this->filesFieldName};
                $dataForComponent = [];
                foreach ($this->groups as $id => $config)
                {
                    if (isset($oiginalFilesData[$id]))
                    {
                        $config['items'] = (array) $oiginalFilesData[$id];
                    }

                    $config['owner']        = $this->owner;
                    $config['behavior']     = $this;

                    $dataForComponent[$id] = $config;
                }

                $this->_groups = new ModelFilesGroups([
                    'components' => $dataForComponent,
                ]);
            //}
        }

        return $this->_groups;
    }


    /**
     *
     * Установить значние поля filesFieldName, использую состояние объекта групп.
     * Без сохранения в базу, просто установка значения поля в моделе
     *
     * @return $this
     */
    public function setInstanceFilesFromGroup()
    {
        $result = [];

        if ($components = $this->getFilesGroups()->getComponents())
        {
            foreach ($components as $component)
            {
                if ($items = (array) $component->items)
                {
                    $result[$component->id] = $items;
                }
            }
        }

        $this->owner->{$this->filesFieldName} = $result;
        return $this;
    }






















    /**
     * Есть ли у модели главное изображение?
     * Не делает запрос в базу.
     *
     * @return bool
     */
    public function hasMainImage()
    {
        $mainImage = $this->getFilesGroups()->getComponent('image');

        if ($mainImage)
        {
            if ($mainImage->getFirstSrc())
            {
                return true;
            }
        }

        return false;
    }
    /**
     * Получить адрес главного изображения.
     * Не делает запрос в базу.
     *
     * @return string
     */
    public function getMainImageSrc()
    {
        $mainImage = $this->getFilesGroups()->getComponent('image');

        if ($mainImage)
        {
            if ($mainImage->getFirstSrc())
            {
                return $mainImage->getFirstSrc();
            }
        }


        return null;
    }

    /**
     * Получить src[] изображений
     * Не делает запрос в базу.
     *
     * @return array
     */
    public function getImagesSrc()
    {
        if ($this->getFilesGroups())
        {
            return (array) $this->getFilesGroups()->getComponent('images')->items;
        }

        return [];
    }


    /**
     * Получить src[] изображений
     * Не делает запрос в базу.
     *
     * @return array
     */
    public function getFilesSrc()
    {
        if ($this->getFilesGroups())
        {
            return (array) $this->getFilesGroups()->getComponent('files')->items;
        }

        return [];
    }

    /**
     * @return array
     */
    public function getMainImagesSrc()
    {
        if ($this->getFilesGroups())
        {
            return (array) $this->getFilesGroups()->getComponent('image')->items;
        }

        return [];
    }









    /**
     * @param $optionName
     * @param $fieldName
     * @param null $defaultValue
     * @return mixed
     * @throws Exception
     */
    /*public function getOptionForField($optionName, $fieldName, $defaultValue = null)
    {
        if (!$this->hasField($fieldName))
        {
            throw new Exception("Для данного бехевера не описано это поле");
        }

        $config = $this->getFieldConfig($fieldName);
        return \yii\helpers\ArrayHelper::getValue($config, $optionName, $defaultValue);
    }*/


    /**
     * Применяется ли к полю
     * @param string $fieldName
     * @return bool
     */
    /*public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }*/

    /**
     * Берем настройки для поля
     * @param string $fieldName
     * @return array
     */
    /*public function getFieldConfig($fieldName)
    {
        return $this->hasField($fieldName) ? (array) $this->fields[$fieldName] : [];
    }*/







    /**
     * @param StorageFile $file
     * @param $fieldName
     * @return $this
     */
    /*protected function _appendFile(StorageFile $file, $fieldName)
    {
        $files              = $this->getFiles($fieldName);
        $files[]            = $file->src;
        $this->owner->setAttribute($fieldName, array_unique($files));

        $this->owner->save(false);
        return $this;
    }*/

    /**
     *
     * Вставка файла в нужное поле
     *
     * Привязывается файл к этой сущьности
     * Вставляется src в поле сущьности
     *
     * @param StorageFile $file
     * @param $fieldName
     * @return $this
     */
    /*public function appendFile(StorageFile $file, $fieldName)
    {
        //Вяжем файл к этой сущьности
        $file->setAttributes($this->owner->getRef()->toArray(), false);
        $file->save(false);
        $this->_appendFile($file, $fieldName);

        return $this;
    }*/

    /**
     * @param $fieldName
     * @param $src
     * @return $this
     */
    /*public function detachFile($fieldName, $src)
    {
        $files  = $this->getFiles($fieldName);

        $result = [];
        if ($files)
        {
            foreach ($files as $fileSrc)
            {
                if ($fileSrc != $src)
                {
                    $result[] = $fileSrc;
                }
            }

            $this->owner->setAttribute($fieldName, $result);
            $this->owner->save(false);
        }

        return $this;
    }*/

}