<?php
/**
 * AssetBundle
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 29.12.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base;

/**
 * Class AssetBundle
 * @package skeeks\cms\base
 */
class AssetBundle extends \yii\web\AssetBundle
{
    public function init()
    {
        parent::init();

        /*foreach ($this->js as $key => $js)
        {
            $this->js[$key] = $js . "?" . \Yii::$app->cms->getStaticKey();
        }

        foreach ($this->css as $key => $css)
        {
            $this->css[$key] = $css . "?" . \Yii::$app->cms->getStaticKey();
        }*/
    }
}