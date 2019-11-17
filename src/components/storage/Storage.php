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

use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use yii\helpers\BaseUrl;

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
 * @property Cluster[] $clusters
 *
 * Class Storage
 * @package common\components\Storage
 */
class Storage extends Component
{
    public $components = [];

    /**
     *
     * Загрузить файл в хранилище, добавить в базу, вернуть модель StorageFile
     *
     * @param UploadedFile|string|File $file объект UploadedFile или File или rootPath до файла локально или http:// путь к файлу (TODO:: доделать)
     * @param array                    $data данные для сохранения в базу
     * @param null                     $clusterId идентификатор кластера по умолчанию будет выбран первый из конфигурации
     * @return StorageFile
     * @throws Exception
     */
    public function upload($file, $data = [], $clusterId = null)
    {
        //Для начала всегда загружаем файл во временную диррикторию
        $tmpdir = Dir::runtimeTmp();
        $tmpfile = $tmpdir->newFile();

        $original_file_name = '';

        if ($file instanceof UploadedFile) {
            $extension = File::object($file->name)->getExtension();
            $tmpfile->setExtension($extension);
            $original_file_name = $file->getBaseName();

            if (!$file->saveAs($tmpfile->getPath())) {
                throw new Exception("Файл не загружен во временную диррикторию");
            }
        } else {
            if ($file instanceof File || (is_string($file) && BaseUrl::isRelative($file))) {
                $file = File::object($file);
                $original_file_name = $file->getBaseName();

                $tmpfile->setExtension($file->getExtension());
                $tmpfile = $file->move($tmpfile);
            } else {
                if (is_string($file) && !BaseUrl::isRelative($file)) {
                    $curl_session = curl_init($file);

                    if (!$curl_session) {
                        throw new Exception("Неверная ссылка");
                    }

                    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl_session, CURLOPT_BINARYTRANSFER, true);
                    curl_setopt($curl_session, CURLOPT_FOLLOWLOCATION, true);

                    $file_content = curl_exec($curl_session);

                    curl_close($curl_session);

                    if (!$file_content) {
                        throw new Exception("Не удалось скачать файл: {$file}");
                    }

                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    $pos = strpos($extension, "?");

                    if ($pos === false) {

                    } else {
                        $extension = substr($extension, 0, $pos);
                    }

                    if ($extension) {
                        $tmpfile->setExtension($extension);
                    }

                    $is_file_saved = file_put_contents($tmpfile, $file_content);

                    if (!$is_file_saved) {
                        throw new Exception("Не удалось сохранить файл");
                    }

                    //Если в ссылке нет расширения
                    if (!$extension) {
                        $tmpfile = new File($tmpfile->getPath());

                        try {
                            $mimeType = FileHelper::getMimeType($tmpfile->getPath(), null, false);
                        } catch (InvalidConfigException $e) {
                            throw new Exception("Не удалось пределить расширение файла: ".$e->getMessage());
                        }

                        if (!$mimeType) {
                            throw new Exception("Не удалось пределить расширение файла");
                        }

                        $extensions = FileHelper::getExtensionsByMimeType($mimeType);
                        if ($extensions) {
                            if (in_array("jpg", $extensions)) {
                                $extension = 'jpg';
                            } else {
                                if (in_array("png", $extensions)) {
                                    $extension = 'png';
                                } else {
                                    $extension = $extensions[0];
                                }
                            }

                            $newFile = new File($tmpfile->getPath());
                            $newFile->setExtension($extension);

                            $tmpfile = $tmpfile->copy($newFile);
                        }
                    }

                } else {
                    throw new Exception("Файл должен быть определен как \yii\web\UploadedFile или \skeeks\sx\File или string");
                }
            }
        }

        $newData = [];

        //$data["type"]       = $tmpfile->getType();
        $newData["mime_type"] = $tmpfile->getMimeType();
        $newData["size"] = $tmpfile->size()->getBytes();
        $newData["extension"] = $tmpfile->getExtension();
        $newData["original_name"] = $original_file_name;

        //Елси это изображение
        if ($tmpfile->getType() == 'image') {
            if (extension_loaded('gd')) {
                list($width, $height, $type, $attr) = getimagesize($tmpfile->toString());
                $newData["image_height"] = $height;
                $newData["image_width"] = $width;
            }

        }

        if ($cluster = $this->getCluster($clusterId)) {
            if ($newFileSrc = $cluster->upload($tmpfile)) {
                $newData = array_merge($newData, [
                    "cluster_id"   => $cluster->id,
                    "cluster_file" => $newFileSrc,
                ]);
            }
        }

        $data = array_merge((array) $newData, (array) $data);
        $file = new StorageFile($data);
        $file->save(false);

        return $file;
    }

    protected $_clusters = null;

    /**
     * @return Cluster[]
     */
    public function getClusters()
    {
        if ($this->_clusters === null) {
            ArrayHelper::multisort($this->components, 'priority');

            foreach ($this->components as $id => $data) {
                if (!is_int($id)) {
                    $data['id'] = $id;
                }

                $cluster = \Yii::createObject($data);
                $this->_clusters[$cluster->id] = $cluster;
            }
        }

        return $this->_clusters;
    }


    /**
     * @param null $id
     * @return Cluster
     */
    public function getCluster($id = null)
    {
        if ($id == null) {
            foreach ($this->clusters as $clusterId => $cluster) {
                return $cluster;
            }
        } else {
            return ArrayHelper::getValue($this->clusters, $id);
        }
    }
}
