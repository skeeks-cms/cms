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


    /**
     * @return bool
     */
    protected function _implodeFiles()
    {
        $this->js = (array)$this->js;
        if (count($this->js) <= 1) {
            return true;
        }

        $filtTimes = [$this->className()];
        foreach ($this->js as $js) {
            $filtTimes[] = fileatime($this->sourcePath.'/'.$js);
        }

        $fileName = 'skeeks-auto-'.md5(implode("", $filtTimes)).".js";
        $fileMinJs = \Yii::getAlias('@app/runtime/assets/js/sx/'.$fileName);

        if (file_exists($fileMinJs)) {
            $this->js = [
                $fileName,
            ];

            $this->sourcePath = '@app/runtime/assets/js/sx';

            return true;
        }

        $fileContent = "";
        foreach ($this->js as $js) {
            $fileContent .= file_get_contents($this->sourcePath.'/'.$js);
        }

        if ($fileContent) {
            $file = new File($fileMinJs);
            $file->make($fileContent);

            if (file_exists($fileMinJs)) {
                $this->js = [
                    $fileName,
                ];

                $this->sourcePath = '@app/runtime/assets/js/sx';

                return true;
            }
        }

        return true;
    }
}