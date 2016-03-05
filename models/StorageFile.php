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
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\helpers\ModelFilesGroup;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

use yii\base\Event;

/**
 * This is the model class for table "{{%cms_storage_file}}".
 *
 * @property integer $id
 * @property string $src
 * @property string $absoluteSrc
 * @property string $cluster_id
 * @property string $cluster_file
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $size
 * @property string $type
 * @property string $mime_type
 * @property string $extension
 * @property string $original_name
 * @property string $name_to_save
 * @property string $name
 * @property string $description_short
 * @property string $description_full
 * @property integer $image_height
 * @property integer $image_width
 * @property string $linked_to_model @version > 2.4.*  is depricated
 * @property string $linked_to_value @version > 2.4.*  is depricated
 * @property string $rootSrc
 *
 * @property User $updatedBy
 * @property User $createdBy
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
            [['src'], 'required'],
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'size', 'image_height', 'image_width', 'published_at'], 'integer'],
            [['description_short', 'description_full'], 'string'],
            [['src', 'cluster_file', 'original_name', 'name', 'linked_to_model', 'linked_to_value'], 'string', 'max' => 255],
            [['cluster_id', 'type', 'mime_type', 'extension'], 'string', 'max' => 16],
            [['name_to_save'], 'string', 'max' => 32],
            [['src'], 'unique'],
            [['cluster_id', 'cluster_file'], 'unique', 'targetAttribute' => ['cluster_id', 'cluster_file'], 'message' => Yii::t('app','The combination of Cluster ID and Cluster Src has already been taken.')],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'src' => Yii::t('app', 'Src'),
            'cluster_id' => Yii::t('app', 'Storage'),
            'cluster_file' => Yii::t('app', 'Cluster File'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'size' => Yii::t('app', 'File Size'),
            'type' => Yii::t('app', 'Type'),
            'mime_type' => Yii::t('app', 'File Type'),
            'extension' => Yii::t('app', 'Extension'),
            'original_name' => Yii::t('app', 'Original FileName'),
            'name_to_save' => Yii::t('app', 'Name To Save'),
            'name' => Yii::t('app', 'Name'),
            'description_short' => Yii::t('app', 'Description Short'),
            'description_full' => Yii::t('app', 'Description Full'),
            'image_height' => Yii::t('app', 'Image Height'),
            'image_width' => Yii::t('app', 'Image Width'),
            'linked_to_model' => Yii::t('app', 'Linked To Model'),
            'linked_to_value' => Yii::t('app', 'Linked To Value'),
            'published_at'  => Yii::t('app', 'Published At'),
        ]);
    }


    const TYPE_FILE     = "file";
    const TYPE_IMAGE    = "image";


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampPublishedBehavior::className() => TimestampPublishedBehavior::className()
        ]);
    }


    /**
     * @inheritdoc
     */
    public static function findIdentity($src)
    {
        return static::findOne(['src' => $src]);
    }

    /**
     * @return bool|int
     * @throws \Exception
     */
    public function delete()
    {
        //Сначала удалить файл
        try
        {
            $cluster = $this->cluster();

            $cluster->deleteTmpDir($this->cluster_file);
            $cluster->delete($this->cluster_file);

        } catch (\common\components\storage\Exception $e)
        {
            return false;
        }

        return parent::delete();
    }

    /**
     * @return $this
     */
    public function deleteTmpDir()
    {
        $cluster = $this->cluster();
        $cluster->deleteTmpDir($this->cluster_file);

        return $this;
    }

    /**
     * Тип файла - первая часть mime_type
     * @return string
     */
    public function getFileType()
    {
        $dataMimeType = explode('/', $this->mime_type);
        return (string) $dataMimeType[0];
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        if ($this->getFileType() == 'image')
        {
            return true;
        } else
        {
            return false;
        }
    }

    /**
     * @return \skeeks\cms\components\storage\Cluster
     */
    public function cluster()
    {
        return \Yii::$app->storage->getCluster($this->cluster_id);
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

        if ($this->cluster() instanceof ClusterLocal)
        {
            if (!\Yii::$app->request->hostInfo)
            {
                return $this;
            }

            $src = \Yii::$app->request->hostInfo . $this->src;
        }
        //Елси это изображение
        if ($this->isImage())
        {
            if (extension_loaded('gd'))
            {
                list($width, $height, $type, $attr) = getimagesize($src);
                $this->image_height       = $height;
                $this->image_width        = $width;
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
        return $this->cluster()->getAbsoluteUrl($this->cluster_file);
    }

    /**
     * @return string
     */
    public function getRootSrc()
    {
        return $this->cluster()->getRootSrc($this->cluster_file);
    }
}
