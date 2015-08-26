<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
namespace skeeks\cms\components;
/**
 * Class GiiModule
 * @package skeeks\cms\components
 */
class GiiModule extends \yii\gii\Module
{
    public function init()
    {
        parent::init();

        if (\Yii::$app->cms->giiEnabled == Cms::BOOL_N)
        {
            $this->allowedIPs = [""];
        } else
        {
            //TODO:add merge settings
            $this->allowedIPs = explode(",", \Yii::$app->cms->giiAllowedIPs);
        }


        $class = new \ReflectionClass(\yii\gii\Module::className());
        $dir = dirname($class->getFileName());

        $this->setBasePath($dir);

    }
}