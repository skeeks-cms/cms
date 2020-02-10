<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\traits;

/**
 * @property $image;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait THasImage
{
    /**
     * @var string
     */
    protected $_image = '';

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getImage()
    {
        if ($this->_image === null) {
            return "";
        }

        if (is_array($this->_image) && count($this->_image) == 2) {
            list($assetClassName, $localPath) = $this->_image;
            return (string)\Yii::$app->getAssetManager()->getAssetUrl(\Yii::$app->assetManager->getBundle($assetClassName), $localPath);
        }

        if (is_string($this->_image)) {
            return $this->_image;
        }

        return "";
    }

    /**
     * @param $image
     * @return $this
     */
    public function setImage($image)
    {
        $this->_image = $image;
        return $this;
    }
}