<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.06.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\components\marketplace\models\PackageModel;
use skeeks\yii2\curl\Curl;
use yii\base\Component;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * @property string $packagistUrl
 *
 * Class CmsExtension
 * @package skeeks\cms\models
 */
class CmsExtension extends Model
{
    public $name        = '';
    public $version     = '';
    public $alias       = '';

    /**
     * @var PackageModel
     */
    public $marketplacePackage = null;

    /**
     * @return static[];
     */
    static public function fetchAll()
    {
        $result = [];

        if (\Yii::$app->extensions)
        {
            foreach (\Yii::$app->extensions as $name => $extensionData)
            {
                $result[$name] = new static($extensionData);
            }
        }

        return $result;
    }

    /**
     * @return static[];
     */
    static public function fetchAllWhithMarketplace()
    {
        $result     = self::fetchAll();

        $packages   = PackageModel::fetchInstalls();

        foreach ($result as $name => $extension)
        {
            if ($model = ArrayHelper::getValue($packages, $name))
            {
                $extension->marketplacePackage = $model;
            }
        }

        return $result;
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name'      => 'Название',
            'version'   => 'Установленная версия',
            'alias'     => 'Алиасы',
        ]);
    }


    /**
     * @return string
     */
    public function getPackagistUrl()
    {
        return 'https://packagist.org/packages/' . $this->name;
    }
}