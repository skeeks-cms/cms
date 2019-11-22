<?php
/**
 * StorageFile
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\components\storage\ClusterLocal;
use skeeks\cms\models\behaviors\CanBeLinkedToModel;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\helpers\ModelFilesGroup;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_storage_file}}".
 *
 * @property integer $id
 * @property string $cluster_id
 * @property string $cluster_file
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $size
 * @property string $mime_type
 * @property string $extension
 * @property string $original_name
 * @property string $name_to_save
 * @property string $name
 * @property string $description_short
 * @property string $description_full
 * @property integer $image_height
 * @property integer $image_width
 * @property integer $priority
 *
 * @property string $fileName
 * @property string $src
 * @property string $absoluteSrc
 * @property string $downloadName
 *
 * @property \skeeks\cms\components\storage\Cluster $cluster
 */
class StorageFile extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_storage_file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['created_by', 'priority', 'updated_by', 'created_at', 'updated_at', 'size', 'image_height', 'image_width'],
                'integer'
            ],
            [['description_short', 'description_full'], 'string'],
            [['cluster_file', 'original_name', 'name'], 'string', 'max' => 255],
            [['cluster_id', 'mime_type', 'extension'], 'string', 'max' => 16],
            [['name_to_save'], 'string', 'max' => 32],
            [
                ['cluster_id', 'cluster_file'],
                'unique',
                'targetAttribute' => ['cluster_id', 'cluster_file'],
                'message' => Yii::t('skeeks/cms',
                    'The combination of Cluster ID and Cluster Src has already been taken.')
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'cluster_id' => Yii::t('skeeks/cms', 'Storage'),
            'cluster_file' => Yii::t('skeeks/cms', 'Cluster File'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'size' => Yii::t('skeeks/cms', 'File Size'),
            'mime_type' => Yii::t('skeeks/cms', 'File Type'),
            'extension' => Yii::t('skeeks/cms', 'Extension'),
            'original_name' => Yii::t('skeeks/cms', 'Original FileName'),
            'name_to_save' => Yii::t('skeeks/cms', 'Name To Save'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'description_short' => Yii::t('skeeks/cms', 'Description Short'),
            'description_full' => Yii::t('skeeks/cms', 'Description Full'),
            'image_height' => Yii::t('skeeks/cms', 'Image Height'),
            'image_width' => Yii::t('skeeks/cms', 'Image Width'),
        ]);
    }


    const TYPE_FILE = "file";
    const TYPE_IMAGE = "image";


    /**
     * @return bool|int
     * @throws \Exception
     */
    public function delete()
    {
        //Сначала удалить файл
        try {
            $cluster = $this->cluster;

            $cluster->deleteTmpDir($this->cluster_file);
            $cluster->delete($this->cluster_file);

        } catch (\common\components\storage\Exception $e) {
            return false;
        }

        return parent::delete();
    }

    /**
     * @return $this
     */
    public function deleteTmpDir()
    {
        $this->cluster->deleteTmpDir($this->cluster_file);

        return $this;
    }

    /**
     * Тип файла - первая часть mime_type
     * @return string
     */
    public function getFileType()
    {
        $dataMimeType = explode('/', $this->mime_type);
        return (string)$dataMimeType[0];
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        if ($this->getFileType() == 'image') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * TODO: Переписать нормально
     * Обновление информации о файле
     *
     * @return $this
     */
    public function updateFileInfo()
    {
        $src = $this->src;

        if ($this->cluster instanceof ClusterLocal) {
            if (!\Yii::$app->request->hostInfo) {
                return $this;
            }

            $src = \Yii::$app->request->hostInfo . $this->src;
        }
        //Елси это изображение
        if ($this->isImage()) {
            if (extension_loaded('gd')) {
                list($width, $height, $type, $attr) = getimagesize($src);
                $this->image_height = $height;
                $this->image_width = $width;
            }
        }

        $this->save();
        return $this;
    }


    /**
     * @return string
     */
    public function getAbsoluteSrc()
    {
        return $this->cluster->getAbsoluteUrl($this->cluster_file);
    }

    /**
     * Путь к файлу
     * @return string
     */
    public function getSrc()
    {
        return $this->cluster->getPublicSrc($this->cluster_file);
    }


    /**
     * @return \skeeks\cms\components\storage\Cluster
     */
    public function getCluster()
    {
        return \Yii::$app->storage->getCluster($this->cluster_id);
    }

    public function fields()
    {
        return ArrayHelper::merge(parent::fields(), [
            'src', 
            'absoluteSrc', 
            'rootSrc', 
        ]);
    }
    /**
     * @return string
     */
    public function getFileName()
    {
        if ($this->original_name) {
            return $this->original_name;
        } else {
            return $this->cluster_file;
        }
    }

    /**
     * TODO::is depricated version > 2.6.0
     * @return \skeeks\cms\components\storage\Cluster
     */
    public function cluster()
    {
        return $this->cluster;
    }

    /**
     * TODO::is depricated version > 2.6.0
     * @return string
     */
    public function getRootSrc()
    {
        return $this->cluster->getRootSrc($this->cluster_file);
    }

    /**
     * @return StorageFile
     */
    public function copy()
    {
        $newFile = \Yii::$app->storage->upload($this->absoluteSrc);

        $newFile->name = $this->name;
        $newFile->description_full = $this->description_full;
        $newFile->description_short = $this->description_short;
        $newFile->name_to_save = $this->name_to_save;
        $newFile->original_name = $this->original_name;
        $newFile->priority = $this->priority;
        $newFile->save();

        return $newFile;
    }


    /**
     * Название файла для сохранения
     *
     * @return string
     */
    public function getDownloadName()
    {
        if ($this->name_to_save) {
            return $this->name_to_save . "." . $this->extension;
        } else {
            return $this->original_name;
        }
    }
}



