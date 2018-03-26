<?php
/**
 * Filter
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\imaging;

use Faker\Provider\File;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Class Filter
 * @package skeeks\cms\components\imaging
 */
abstract class Filter extends Component
{
    protected $_config = [];

    protected $_originalRootFilePath = null;
    protected $_newRootFilePath = null;

    public function __construct($config = [])
    {
        $this->_config = $config;
        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return str_replace("\\", '-', $this->className());
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param $originalFilePath
     * @return $this
     */
    public function setOriginalRootFilePath($originalRootFilePath)
    {
        $this->_originalRootFilePath = (string)$originalRootFilePath;
        return $this;
    }

    /**
     * @param $originalFilePath
     * @return $this
     */
    public function setNewRootFilePath($newRootFilePath)
    {
        $this->_newRootFilePath = (string)$newRootFilePath;
        return $this;
    }


    /**
     * @return \skeeks\sx\File
     * @throws \ErrorException
     */
    public function save()
    {
        if (!$this->_originalRootFilePath) {
            throw new \ErrorException("not configurated original file");
        }

        if (!$this->_newRootFilePath) {
            throw new \ErrorException("not configurated new file path");
        }

        //Все проверки прошли, результирующая дирректория создана или найдена, результирующий файл можно перезаписывать если он существует
        //try
        //{
        $this->_createNewDir();
        $this->_save();
        $file = new \skeeks\sx\File($this->_newRootFilePath);

        if (!$file->isExist()) {
            throw new \ErrorException('Файл не найден');
        }

        //} catch (\Cx_Exception $e)
        //{
        //    throw new \ErrorException($e->getMessage());
        //}

        return $file;
    }


    /**
     * @return $this
     */
    protected function _createNewDir()
    {
        $newFile = new \skeeks\sx\File($this->_newRootFilePath);

        if (!FileHelper::createDirectory($newFile->getDir()->getPath())) {
            throw new \ErrorException("Не удалось создать диррикторию для нового файла");
        }

        return $this;
    }

    abstract protected function _save();
}