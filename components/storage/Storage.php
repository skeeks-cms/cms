<?php
/**
 * Storage
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\storage;

use skeeks\cms\components\CollectionComponents;
use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\web\UploadedFile;

use \skeeks\sx\File;
use \skeeks\sx\Dir;



/*interface Storage
{
    public function add($file);
    public function update($storageFileSrc, $file);
    public function delete($storageFileSrc);


}*/

/**
 *
 * @method Cluster[]   getComponents()
 * @method Cluster     getComponent($id)
 *
 *
 * Class Storage
 * @package common\components\Storage
 */
class Storage extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\components\storage\ClusterLocal';

    /**
     *
     * Загрузить файл в хранилище, добавить в базу, вернуть модель StorageFile
     *
     * @param UploadedFile|string|File $file    объект UploadedFile или File или rootPath до файла локально или http:// путь к файлу (TODO:: доделать)
     * @param array $data                       данные для сохранения в базу
     * @param null $clusterId                   идентификатор кластера по умолчанию будет выбран первый из конфигурации
     * @return StorageFile
     * @throws Exception
     */
    public function upload($file, $data = [], $clusterId = null)
    {
        //Для начала всегда загружаем файл во временную диррикторию
        $tmpdir         = Dir::runtimeTmp();
        $tmpfile        = $tmpdir->newFile();

        if ($file instanceof UploadedFile)
        {
            $extension  = File::object($file->name)->getExtension();
            $tmpfile->setExtension($extension);

            if (!$file->saveAs($tmpfile->getPath()))
            {
                throw new Exception("Файл не загружен во временную диррикторию");
            }
        } else if ($file instanceof File || is_string($file))
        {
            $file       = File::object($file);
            $tmpfile->setExtension($file->getExtension());
            $tmpfile    = $file->move($tmpfile);
        } else
        {
            throw new Exception("Файл должен быть определен как \yii\web\UploadedFile или \skeeks\sx\File или string");
        }

        ;
        $data["type"]       = $tmpfile->getType();
        $data["mime_type"]  = $tmpfile->getMimeType();
        $data["size"]       = $tmpfile->size()->getBytes();
        $data["extension"]  = $tmpfile->getExtension();

        if ($cluster = $this->getCluster($clusterId))
        {
            if ($newFileSrc = $cluster->upload($tmpfile))
            {
                $data = array_merge($data,
                [
                    "src"           => $cluster->getPublicSrc($newFileSrc),
                    "cluster_id"    => $cluster->getId(),
                    "cluster_file"  => $newFileSrc,
                ]);
            }
        }

        $file = new StorageFile($data);
        $file->save(false);

        return $file;
    }



    /**
     * @return array
     */
    public function getClusters()
    {
        return $this->getComponents();
    }

    /**
     * @param null $id
     * @return Cluster
     */
    public function getCluster($id = null)
    {
        if ($id == null)
        {
            foreach ($this->getComponents() as $clusterId => $cluster)
            {
                return $cluster;
            }
        } else
        {
            return $this->getComponent($id);
        }
    }
}
