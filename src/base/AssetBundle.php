<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

use skeeks\sx\File;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AssetBundle extends \yii\web\AssetBundle
{
    /**
     * @param string $asset
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function getAssetUrl($asset)
    {
        return \Yii::$app->assetManager->getAssetUrl(\Yii::$app->assetManager->getBundle(static::className()), $asset);
    }

    protected $originalSourcePath = null;

    /**
     * @return $this
     */
    protected function _implodeFiles()
    {
        $this->js = (array)$this->js;
        $this->css = (array)$this->css;

        if (count($this->js) <= 1 && count($this->css) <= 1) {
            return $this;
        }

        $this->originalSourcePath = $this->sourcePath;

        $this->_implodeJs();
        $this->_implodeCss();

        return $this;
    }

    /**
     * @return $this
     */
    protected function _implodeJs()
    {
        if (!$this->js) {
            return $this;
        }

        $filtTimes = [$this->className()];
        foreach ($this->js as $js) {
            $filtTimes[] = fileatime($this->originalSourcePath.'/'.$js);
        }

        $fileName = 'skeeks-auto-'.md5(implode("", $filtTimes)).".js";
        $fileMinJs = \Yii::getAlias('@app/runtime/assets/skeeks-auto/'.$fileName);

        if (file_exists($fileMinJs)) {
            $this->js = [
                $fileName,
            ];

            $this->sourcePath = '@app/runtime/assets/skeeks-auto';

            return $this;
        }

        $fileContent = "";
        foreach ($this->js as $js) {
            $fileContent .= file_get_contents(\Yii::getAlias($this->originalSourcePath.'/'.$js));

            /*$f = fopen(\Yii::getAlias($this->originalSourcePath.'/'.$js), "r+");
            $fileContent .= fgets($f);
            fclose($f);*/
        }

        if ($fileContent) {
            $file = new File($fileMinJs);
            $file->make($fileContent);

            if (file_exists($fileMinJs)) {
                $this->js = [
                    $fileName,
                ];

                $this->sourcePath = '@app/runtime/assets/skeeks-auto';

                return $this;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _implodeCss()
    {
        if (!$this->css) {
            return $this;
        }


        $filtTimes = [$this->className()];
        foreach ($this->css as $js) {
            $filtTimes[] = fileatime($this->originalSourcePath.'/'.$js);
        }

        $fileName = 'skeeks-auto-'.md5(implode("", $filtTimes)).".css";
        $fileMinJs = \Yii::getAlias('@app/runtime/assets/skeeks-auto/'.$fileName);

        if (file_exists($fileMinJs)) {
            $this->css = [
                $fileName,
            ];

            $this->sourcePath = '@app/runtime/assets/skeeks-auto';

            return $this;
        }

        $fileContent = "";
        foreach ($this->css as $js) {
            /*$f = fopen(\Yii::getAlias($this->originalSourcePath.'/'.$js), "r+");
            $fileContent .= fgets($f);
            fclose($f);*/

            $fileContent .= file_get_contents(\Yii::getAlias($this->originalSourcePath.'/'.$js));
        }
        if ($fileContent) {
            $file = new File($fileMinJs);
            $file->make($fileContent);

            if (file_exists($fileMinJs)) {
                $this->css = [
                    $fileName,
                ];

                $this->sourcePath = '@app/runtime/assets/skeeks-auto';

                return $this;
            }
        }

        return $this;
    }
}