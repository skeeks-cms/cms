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

use common\models\User;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\StorageFile;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

/**
 * @property ActiveRecord|User $owner
 *
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

    /**
     * @var array
     */
    public $nameAttribute = 'name';

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

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE => "deleteStorgaFile",

            BaseActiveRecord::EVENT_BEFORE_INSERT => "saveStorgaFile",
            BaseActiveRecord::EVENT_BEFORE_UPDATE => "saveStorgaFile",

            BaseActiveRecord::EVENT_AFTER_INSERT => "afterSaveStorgaFile",
            BaseActiveRecord::EVENT_AFTER_UPDATE => "afterSaveStorgaFile",
        ];
    }

    /**
     * Загрузка файлов в хранилище и их сохранение со связанной сущьностью
     *
     * @param $e
     */
    public function saveStorgaFile($e)
    {
        foreach ($this->fields as $fieldCode) {
            /**
             * Удалить старые файлы
             */
            if ($this->owner->isAttributeChanged($fieldCode)) {
                if ($this->owner->getOldAttribute($fieldCode) && $this->owner->getOldAttribute($fieldCode) != $this->owner->{$fieldCode}) {
                    $this->_removeFiles[] = $this->owner->getOldAttribute($fieldCode);
                }
            }

            if ($this->owner->{$fieldCode} && is_string($this->owner->{$fieldCode}) && ((string)(int)$this->owner->{$fieldCode} != (string)$this->owner->{$fieldCode})) {
                try {
                    $data = [];

                    if (isset($this->owner->{$this->nameAttribute})) {
                        if ($name = $this->owner->{$this->nameAttribute}) {
                            $data['name'] = $name;
                        }
                    }

                    $file = \Yii::$app->storage->upload($this->owner->{$fieldCode}, $data);
                    if ($file) {
                        $this->owner->{$fieldCode} = $file->id;
                    } else {
                        $this->owner->{$fieldCode} = null;
                    }

                } catch (\Exception $e) {
                    \Yii::error($e->getMessage());
                    $this->owner->{$fieldCode} = null;
                }
            }
        }
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function afterSaveStorgaFile()
    {
        if ($this->_removeFiles) {
            if ($files = StorageFile::find()->where(['id' => $this->_removeFiles])->all()) {
                foreach ($files as $file) {
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
        if (!$this->onDeleteCascade) {
            return $this;
        }

        foreach ($this->fields as $fieldValue) {
            if ($fileId = $this->owner->{$fieldValue}) {
                if ($storageFile = CmsStorageFile::findOne($fileId)) {
                    $storageFile->delete();
                }
            }
        }
    }
}