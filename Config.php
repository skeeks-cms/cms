<?php
/**
 * Config
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms;
use yii\base\Exception;

/**
 * Class Config
 * @package skeeks\cms
 */
class Config
{
    public $files           = [];
    public $cache           = true;
    public $cacheDir        = '';
    public $cacheDependency = [YII_ENV, YII_ENV_DEV, PHP_VERSION];
    public $name            = "config";
    public $result          = null;

    public function __construct($files = [])
    {
        $this->files = $files;
    }

    /**
     * @param $dependency
     */
    public function appendDependency($dependency)
    {
        if (is_string($dependency) || is_int($dependency))
        {
            $this->cacheDependency = array_merge($this->cacheDependency, [$dependency]);
        } else if (is_array($dependency))
        {
            $this->cacheDependency = array_merge($this->cacheDependency, $dependency);
        }

    }
    /**
     * @param $files
     * @throws Exception
     */
    public function appendFiles($files)
    {
        if (is_string($files))
        {
            $this->files = array_merge($this->files, [$files]);
        } else if (is_array($files))
        {
            $this->files = array_merge($this->files, $files);
        } else
        {
            throw new Exception;
        }

        return $this;
    }
    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->name . '__' . md5(
            implode("", (array) $this->cacheDependency)
            //TODO: подумать
            //implode("", (array) $this->files)
        ) . '.cache.conf';
    }

    /**
     * @return string
     */
    public function getCacheFile()
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $this->getCacheKey();
    }

    /**
     * @return bool
     */
    public function cacheIsAllow()
    {
        $isAllow = (bool) ($this->cache && $this->cacheDir);
        return $isAllow;
    }

    /**
     * @return array
     */
    public function readCache()
    {
        if ($this->existCache())
        {
            \Yii::beginProfile('read cache: ' . $this->name);
                $this->result = (array) unserialize(file_get_contents($this->getCacheFile()));
            \Yii::endProfile('read cache: ' . $this->name);
        } else
        {
            $this->result = $this->merge();
            $this->saveCache();
        }

        return $this->result;
    }

    /**
     * @return $this
     */
    public function saveCache()
    {
        \Yii::beginProfile('save cache: ' . $this->name);
        \Yii::trace('save cache ' . $this->name . ' file: ' . $this->getCacheFile());
            $file = fopen($this->getCacheFile(), "w");
            fwrite($file, serialize($this->result));
            fclose($file);
        \Yii::endProfile('save cache: ' . $this->name);

        return $this;
    }

    /**
     * @return bool
     */
    public function existCache()
    {
        return file_exists($this->getCacheFile());
    }

    /**
     * @return array
     */
    public function getResult()
    {
        \Yii::beginProfile('get config: ' . $this->name);

        if ($this->result === null)
        {
            \Yii::trace('cache allow ' . $this->name . ': ' . (int) $this->cacheIsAllow());
            //Разрешено использовать кэш
            if ($this->cacheIsAllow())
            {
                \Yii::trace('cache read ' . $this->name . ' : ' . $this->getCacheFile());
                $this->result = (array) $this->readCache();

                if (count($this->result) < 5)
                {
                    \Yii::trace('cache file is empty ' . $this->name . ' : ' . $this->getCacheFile());
                    $this->result = (array) $this->merge();
                    $this->saveCache();
                }

            } else
            {
                $this->result = $this->merge();
            }
        }

        \Yii::endProfile('get config: ' . $this->name);
        return (array) $this->result;
    }

    /**
     * @return array
     */
    public function merge()
    {
        \Yii::beginProfile('merge config: ' . $this->name);

        $result = [];

        if ($this->files)
        {
            $this->files = array_unique($this->files);

            foreach ($this->files as $file)
            {
                if (file_exists($file))
                {
                    $fileData = (array) include $file;
                    $result = \yii\helpers\ArrayHelper::merge($result, $fileData);
                }
            }
        }

        \Yii::endProfile('merge config: ' . $this->name);

        return $result;
    }


}