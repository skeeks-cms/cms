<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

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
}